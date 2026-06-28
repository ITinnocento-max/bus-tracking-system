/********************************************************************
    SMART IoT BUS TRACKING SYSTEM (PRODUCTION VERSION)

    ESP32-WROOM-32 + SIM900 + GPS + IR SEATS

    FEATURES:
    v Real-time GPS tracking
    v Multi-bus support
    v Seat monitoring (4 seats)
    v PHP/MySQL backend integration
    v SMS ticket delivery from database
    v SIM900 GPRS HTTP communication
    v Automatic reconnect system
********************************************************************/

#include <TinyGPS++.h>
#include <HardwareSerial.h>
#include <ArduinoJson.h>

/********************************************************************
                    HARDWARE CONFIG
********************************************************************/

HardwareSerial gpsSerial(1);
HardwareSerial sim900(2);
TinyGPSPlus gps;

/********************************************************************
                    PIN CONFIGURATION
********************************************************************/

#define GPS_RX 16
#define GPS_TX 17

#define SIM_RX 26
#define SIM_TX 27

#define SEAT1 32
#define SEAT2 33
#define SEAT3 34
#define SEAT4 35

/********************************************************************
                    SYSTEM CONFIG
********************************************************************/

String BUS_ID = "BUS001";
String APN = "internet";

/********************************************************************
                    SERVER ENDPOINTS
********************************************************************/

String URL_UPDATE  = "http://bustracking.kesug.com/api/update.php";
String URL_SMS     = "http://bustracking.kesug.com/api/get_pending_sms.php";
String URL_CONFIRM = "http://bustracking.kesug.com/api/confirm_sms.php";

/********************************************************************
                    GLOBAL VARIABLES
********************************************************************/

String lat = "0.0";
String lng = "0.0";

int seat1, seat2, seat3, seat4;

bool gprsReady = false;

/********************************************************************
                    SETUP
********************************************************************/

void setup()
{
    Serial.begin(115200);

    gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);
    sim900.begin(9600, SERIAL_8N1, SIM_RX, SIM_TX);

    pinMode(SEAT1, INPUT);
    pinMode(SEAT2, INPUT);
    pinMode(SEAT3, INPUT);
    pinMode(SEAT4, INPUT);

    delay(3000);

    initSIM900();

    Serial.println("SYSTEM READY");
}

/********************************************************************
                    MAIN LOOP
********************************************************************/

void loop()
{
    readGPS();
    readSeats();

    if (!gprsReady) {
        reconnectGPRS();
    }

    if (gprsReady) {
        sendBusData();
        checkPendingSMS();
    }

    delay(8000);
}

/********************************************************************
                    GPS HANDLER
********************************************************************/

void readGPS()
{
    while (gpsSerial.available()) {
        gps.encode(gpsSerial.read());
    }

    if (gps.location.isValid()) {
        lat = String(gps.location.lat(), 6);
        lng = String(gps.location.lng(), 6);
    }
}

/********************************************************************
                    SEAT READING
********************************************************************/

void readSeats()
{
    seat1 = digitalRead(SEAT1);
    seat2 = digitalRead(SEAT2);
    seat3 = digitalRead(SEAT3);
    seat4 = digitalRead(SEAT4);
}

/********************************************************************
                    SIM900 INIT
********************************************************************/

void initSIM900()
{
    Serial.println("[SIM900] Initializing...");

    sendAT("AT", 2000);
    sendAT("ATE0", 1000);

    // Check SIM ready
    if (!checkSIM()) return;

    // Check network registration
    if (!waitForNetwork()) return;

    // Signal quality
    checkSignal();

    // Setup GPRS bearer
    setupGPRS();
}

bool checkSIM()
{
    String r = sendAT("AT+CPIN?", 3000);
    if (r.indexOf("READY") >= 0) {
        Serial.println("[SIM] SIM ready");
        return true;
    }
    Serial.println("[SIM] SIM not ready - check PIN");
    return false;
}

bool waitForNetwork()
{
    Serial.println("[NET] Waiting for network registration...");
    for (int i = 0; i < 30; i++) {
        String r = sendAT("AT+CREG?", 2000);
        if (r.indexOf("+CREG: 0,1") >= 0 || r.indexOf("+CREG: 0,5") >= 0) {
            Serial.println("[NET] Registered");
            return true;
        }
        delay(1000);
    }
    Serial.println("[NET] Registration failed");
    return false;
}

void checkSignal()
{
    String r = sendAT("AT+CSQ", 2000);
    Serial.println("[SIG] " + r);
}

void setupGPRS()
{
    Serial.println("[GPRS] Setting up bearer...");

    sendAT("AT+SAPBR=3,1,\"CONTYPE\",\"GPRS\"", 2000);

    String cmd = "AT+SAPBR=3,1,\"APN\",\"" + APN + "\"";
    sendAT(cmd, 2000);

    String resp = sendAT("AT+SAPBR=1,1", 15000);
    if (resp.indexOf("OK") >= 0) {
        Serial.println("[GPRS] Bearer active");
        gprsReady = true;
    } else {
        // Could be already active — check status
        String stat = sendAT("AT+SAPBR=2,1", 3000);
        if (stat.indexOf("+SAPBR: 1,1,") >= 0) {
            Serial.println("[GPRS] Bearer already active");
            gprsReady = true;
        } else {
            Serial.println("[GPRS] Bearer activation failed");
            gprsReady = false;
        }
    }
}

/********************************************************************
                    FLUSH SIM900 RX BUFFER
********************************************************************/

void flushSIM900()
{
    while (sim900.available()) {
        sim900.read();
    }
}

/********************************************************************
                    SEND AT COMMAND
********************************************************************/

String sendAT(String cmd, int timeout)
{
    flushSIM900();

    String response = "";
    sim900.println(cmd);

    long start = millis();
    while ((millis() - start) < timeout) {
        while (sim900.available()) {
            char c = sim900.read();
            response += c;
            if (response.endsWith("\r\nOK\r\n") || response.endsWith("\r\nERROR\r\n")) {
                goto done;
            }
        }
    }
    done:

    // Drain any leftover bytes so next call starts clean
    while (sim900.available()) {
        sim900.read();
    }

    response.trim();
    if (response.length() > 0) {
        Serial.println("[AT] " + response);
    }
    return response;
}

/********************************************************************
                    SEND BUS DATA TO SERVER
********************************************************************/

void sendBusData()
{
    String url =
        URL_UPDATE +
        "?bus_id=" + BUS_ID +
        "&lat=" + lat +
        "&lng=" + lng +
        "&s1=" + String(seat1) +
        "&s2=" + String(seat2) +
        "&s3=" + String(seat3) +
        "&s4=" + String(seat4);

    String response = httpGET(url);
    if (response.indexOf("OK") >= 0) {
        Serial.println("[DATA] Server OK");
    } else if (response.indexOf("ERROR") >= 0) {
        Serial.println("[DATA] Server error: " + response);
    }
}

/********************************************************************
                    HTTP GET VIA SIM900
********************************************************************/

String httpGET(String url)
{
    if (!gprsReady) return "";
    String response = "";

    // Try setting URL directly (reuses existing HTTP session if alive)
    String cmd = "AT+HTTPPARA=\"URL\",\"" + url + "\"";
    String r = sendAT(cmd, 2000);

    if (r.indexOf("ERROR") >= 0) {
        // Session dead — full re-init
        sendAT("AT+HTTPTERM", 1000);
        delay(500);

        r = sendAT("AT+HTTPINIT", 4000);
        if (r.indexOf("ERROR") >= 0) {
            Serial.println("[HTTP] Init failed, reconnecting GPRS...");
            reconnectGPRS();
            if (!gprsReady) return "";
            r = sendAT("AT+HTTPINIT", 4000);
            if (r.indexOf("ERROR") >= 0) return "";
        }

        sendAT("AT+HTTPPARA=\"CID\",1", 1000);
        sendAT(cmd, 2000);
    }

    // Execute GET
    sendAT("AT+HTTPACTION=0", 8000);

    // Read response
    response = sendAT("AT+HTTPREAD", 3000);

    return response;
}

/********************************************************************
                    CHECK PENDING SMS
********************************************************************/

void checkPendingSMS()
{
    String response = httpGET(URL_SMS + "?bus_id=" + BUS_ID);

    if (response.indexOf("{") == -1) return;

    StaticJsonDocument<512> doc;
    DeserializationError err = deserializeJson(doc, response);

    if (err) return;

    if (doc["status"] != "success") return;

    String phone    = doc["phone"];
    String message  = doc["message"];
    String ticket_id = doc["ticket_id"];

    bool sent = sendSMS(phone, message);

    if (sent) {
        confirmSMS(ticket_id);
    }
}

/********************************************************************
                    SEND SMS
********************************************************************/

bool sendSMS(String phone, String message)
{
    sendAT("AT+CMGF=1", 1000);

    sim900.print("AT+CMGS=\"");
    sim900.print(phone);
    sim900.println("\"");
    delay(1000);

    sim900.print(message);
    delay(500);
    sim900.write(26);
    delay(5000);

    String resp = "";
    while (sim900.available()) {
        resp += char(sim900.read());
    }
    Serial.println("[SMS] " + resp);

    return resp.indexOf("OK") != -1;
}

/********************************************************************
                    CONFIRM SMS DELIVERY
********************************************************************/

void confirmSMS(String ticket_id)
{
    httpGET(URL_CONFIRM + "?ticket_id=" + ticket_id);
}

/********************************************************************
                    AUTO GPRS RECOVERY
********************************************************************/

void reconnectGPRS()
{
    Serial.println("[RECONNECT] GPRS bearer...");
    gprsReady = false;

    sendAT("AT+HTTPTERM", 1000);
    delay(300);

    // Check if network is still registered
    String r = sendAT("AT+CREG?", 2000);
    if (r.indexOf("+CREG: 0,1") < 0 && r.indexOf("+CREG: 0,5") < 0) {
        Serial.println("[RECONNECT] Network lost, re-registering...");
        sendAT("AT+CFUN=1,1", 5000);
        delay(10000);
        if (!waitForNetwork()) {
            return;
        }
    }

    // Retry GPRS attach
    setupGPRS();
}
