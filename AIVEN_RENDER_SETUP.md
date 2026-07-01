# Aiven MySQL + Render Setup Guide

## 1. Create Aiven MySQL Database

1. Go to [https://console.aiven.io](https://console.aiven.io) and sign up/log in
2. Click **"Create Service"**
3. Select **"MySQL"**
4. Choose plan:
   - **Free**: 1 GB RAM, 5 GB storage (enough for testing)
   - **Startup**: $15/mo (for production)
5. Select a cloud region (choose one close to Render's Oregon region)
6. Name your service (e.g., `bus-tracking-db`)
7. Click **"Create Service"** (takes 2-5 minutes to provision)

## 2. Get Connection Details

Once the service is ready:

1. Go to the **"Overview"** tab
2. Note the **Host** and **Port** (usually `mysql-xxxxx.aivencloud.com:25060`)
3. Go to the **"Connection Information"** section
4. Note:
   - **User**: `avnadmin`
   - **Password**: (click the eye icon to reveal)
5. Click **"Download CA Certificate"** – save the `.pem` file

### Optional: Create a custom database

By default Aiven creates `defaultdb`. To create `bus_tracking_db`:

```bash
# Using Aiven CLI
avn service create bus-tracking-db --service-type mysql --plan free

# Or connect directly and create:
mysql -h mysql-xxxxx.aivencloud.com -P 25060 -u avnadmin -p defaultdb
CREATE DATABASE bus_tracking_db;
```

## 3. Set Environment Variables in Render

1. Go to [https://dashboard.render.com](https://dashboard.render.com)
2. Select your **"bus-tracking-system"** service
3. Go to **"Environment"** tab
4. Click **"Add Environment Variable"** for each:

| Variable | Value |
|----------|-------|
| `APP_ENV` | `production` |
| `DB_HOST` | `mysql-xxxxx.aivencloud.com` (your Aiven host) |
| `DB_PORT` | `25060` |
| `DB_NAME` | `defaultdb` (or `bus_tracking_db` if you created it) |
| `DB_USER` | `avnadmin` |
| `DB_PASSWORD` | Your Aiven password |
| `DB_SSL_CA` | **Paste the entire CA certificate content** (see below) |
| `GOOGLE_MAPS_API_KEY` | Your Google Maps API key |

### Setting `DB_SSL_CA`:

The Aiven CA cert is multi-line PEM text. Copy the entire content of the downloaded `.pem` file and paste it as the value — including the `-----BEGIN CERTIFICATE-----` and `-----END CERTIFICATE-----` lines.

```
-----BEGIN CERTIFICATE-----
MIIEQTCCAqmgAwIBAgIUVETGZgQ1... (full cert content)
-----END CERTIFICATE-----
```

## 4. Deploy to Render

1. Push your code to GitHub (if not done yet)
2. Render will auto-deploy when you push (or click **"Manual Deploy"** → **"Deploy latest commit"**)
3. Wait for the build and deploy to complete (2-5 minutes)

## 5. Initialize Database Tables

Once the app is deployed:

1. Visit: `https://your-app.onrender.com/db_setup.php?key=setup2026`
2. You should see **"Database Setup Complete"** with a list of tables
3. **IMPORTANT**: Delete `db_setup.php` from your repo after use, or protect it — it's a security risk

## 6. Verify Everything

- Visit `https://your-app.onrender.com/api/get_buses.php` — should return JSON bus data
- Try registering/logging in at `/auth/register.php` and `/auth/login.php`
- Check bookings and seat data

## Troubleshooting

| Problem | Likely Cause | Fix |
|---------|-------------|-----|
| `Connection refused` | Wrong host or port | Double-check Aiven host + port (`25060`) |
| `SSL connection error` | Missing/wrong CA cert | Re-paste the CA PEM content in `DB_SSL_CA` |
| `Access denied for user` | Wrong username/password | Verify `avnadmin` and password from Aiven console |
| `Unknown database` | DB name doesn't exist | Use `defaultdb` or create the DB on Aiven |
| `PDOException: Service unavailable` | Can't connect at all | Check if Aiven service is running (whitelist IPs if needed) |

### Allowing Render IPs on Aiven

Aiven Free plan allows all IPs by default. If you get connection errors:

1. In Aiven console → your MySQL service → **"Connection Details"**
2. Under **"IP Allowlist"**, add `0.0.0.0/0` (allow all)
3. Or add Render's outbound IPs for Oregon region
