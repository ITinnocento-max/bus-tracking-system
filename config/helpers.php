<?php
function icon($n,$c='icon'){
  $p=__DIR__.'/../assets/icons/'.$n.'.svg';
  if(!is_file($p))return'';
  $s=file_get_contents($p);
  if($s===false)return'';
  return str_replace('<svg','<svg class="'.htmlspecialchars($c).'"',$s);
}
