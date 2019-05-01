<?php
require('config.php'); //ดึง การตั้งค่า  ;ต่างๆ
class Rcon{   private $host;   private $port;   private $password;   private $timeout;   private $socket;   private $authorized = false;   private $lastResponse = '';   
      const PACKET_AUTHORIZE = 5;   const PACKET_COMMAND = 6;   const SERVERDATA_AUTH = 3;   const SERVERDATA_AUTH_RESPONSE = 2;   const SERVERDATA_EXECCOMMAND = 2;   
	  const SERVERDATA_RESPONSE_VALUE = 0;   
	  public function __construct($host, $port, $password, $timeout)   
	  {       $this->host = $host;       $this->port = $port;       $this->password = $password;       $this->timeout = $timeout;   }   
	  public function getResponse()   
	  {       return $this->lastResponse;   }   
	  public function connect()   
	  {       $this->socket = fsockopen($this->host, $this->port, $errno, $errstr, $this->timeout);       
	  if (!$this->socket) 
	  {           $this->lastResponse = $errstr;           return false;       }       
      stream_set_timeout($this->socket, 3, 0);       return $this->authorize();   }   
	  public function disconnect()   
	  {       if ($this->socket) {                   fclose($this->socket);       }   }   
	  public function isConnected()   
	  {       return $this->authorized;   }   
	  public function sendCommand($command)   
	  {       if (!$this->isConnected()) {                   return false;       }       
	           $this->writePacket(self::PACKET_COMMAND, self::SERVERDATA_EXECCOMMAND, $command);       
	           $response_packet = $this->readPacket();       
	           if ($response_packet['id'] == self::PACKET_COMMAND) 
	           {           if ($response_packet['type'] == self::SERVERDATA_RESPONSE_VALUE) 
	           {               $this->lastResponse = $response_packet['body'];               
              return $response_packet['body'];           }       }       return false;   }   
	  private function authorize()   
	  {       $this->writePacket(self::PACKET_AUTHORIZE, self::SERVERDATA_AUTH, $this->password);       
	          $response_packet = $this->readPacket();       
	          if ($response_packet['type'] == self::SERVERDATA_AUTH_RESPONSE) 
	              {           if ($response_packet['id'] == self::PACKET_AUTHORIZE) 
	              {               $this->authorized = true;               
                  return true;           }       }       
				  $this->disconnect();      
			return false;   }   
	  private function writePacket($packetId, $packetType, $packetBody)   
	  {       $packet = pack('VV', $packetId, $packetType);       
	          $packet = $packet.$packetBody."\x00";       
			  $packet = $packet."\x00";       
			  $packet_size = strlen($packet);       
			  $packet = pack('V', $packet_size).$packet;       
			  fwrite($this->socket, $packet, strlen($packet));   }   
	  private function readPacket()   
	  {       $size_data = fread($this->socket, 4);       
	          $size_pack = unpack('V1size', $size_data);       
		      $size = $size_pack['size'];
			  $packet_data = fread($this->socket, $size);       
			  $packet_pack = unpack('V1id/V1type/a*body', $packet_data);       
			  return $packet_pack;   }}
function APICurl($url) { global $config;	$ch = curl_init();  $post = [	'email' => $config['email'],'password' => $config['password']];curl_setopt($ch, CURLOPT_URL, $url);    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);curl_setopt($ch, CURLOPT_POSTFIELDS, $post);$data = curl_exec($ch);     curl_close($ch);    return $data; }
if(isset($_POST['truemoney'])) { // ตรวจสอบว่ามีกดปุ่มเติมเข้ามา
  if(empty($_POST['username']) || empty($_POST['truemoney'])) {
    exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;กรุณาอย่าเว้นช่องว่าง</div>');
  }
  if(!is_numeric($_POST['truemoney']) || strlen($_POST['truemoney']) != 14 || $_POST['truemoney'] < 1) {
    exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;รหัสบัตรทรูมันนี่ ไม่ถูกต้อง</div>');
  }
    // https://github.com/maythiwat/WalletAPI << API WALLET
    // https://mcbot-th.cf << คนออกแบบเว็บไซต์
    $ijson = APICurl('https://payment-gateway.itorkungz.me/truemoney?card='.$_POST['truemoney'].'&username='.$_POST['username']);
  	$itopup = json_decode($ijson, true); // แปลง json ออกมา
  	$amount = $itopup['amount'] * $config['promotion']; // เอาจำนวนเงิน มาคูณกับโปรโมชั่น

    if($itopup['code'] == 3) {
      file_put_contents("error.txt", "ผู้เล่น ".$_POST['username']. " ได้เติมเงินเมื่อวันที่  ".$time."   สถานะ : ไม่สำเร็จ (อีเมล์ หรือ รหัสผ่านวอเลท ไม่ถูกต้อง)".PHP_EOL, FILE_APPEND);
      exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp; อีเมล์ หรือ รหัสผ่าน วอเลทไม่ถูกต้อง <br> #กรุณาติดต่อแอดมิน</div>');
    }
    if($itopup['code'] == 2) {
      file_put_contents("failed.txt", "ผู้เล่น ".$_POST['username']. " ได้เติมเงินเมื่อวันที่  ".$time."   สถานะ : ถูกใช้ไปแล้ว (".$_POST['truemoney'].")".PHP_EOL, FILE_APPEND);
      exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;บัตรทรู ถูกใช้งานแล้ว</div>');
    }
    if($itopup['code'] == 0) {
      file_put_contents("failed.txt", "ผู้เล่น ".$_POST['username']. " ได้เติมเงินเมื่อวันที่  ".$time."   สถานะ : ไม่สำเร็จ (".$_POST['truemoney'].")".PHP_EOL, FILE_APPEND);
      exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;รหัสบัตรไม่ถูกต้อง</div>');
    }else if($itopup['code'] == 1) {
      $rcon = new Rcon($config['ip_rcon'], $config['port_rcon'], $config['pw_rcon'], 3);
      if($rcon->connect()) {
        $player = str_replace("[player]",$_POST['username'],$command[$itopup['amount']]);
        $iamount = str_replace("[amount]",$amount,$player);
        $explode = explode("[and] ", $iamount);

        foreach ($explode as $kuy) {
          $rcon->sendCommand($kuy);
        }
        file_put_contents("success.txt", "ผู้เล่น ".$_POST['username']. " ได้เติมเงินเมื่อวันที่  ".$time."   สถานะ : สำเร็จ (".$_POST['truemoney']." : ".$itopup['amount'].")".PHP_EOL, FILE_APPEND);
        exit('<div class="alert alert-success"><i class="fas fa-check-square"></i>&nbsp;ว้าวว มึงเติมเงินสำเร็จแล้วนะ <br> จำนวน '.$itopup['amount'].' <br> ได้รับเงินในเกม '.$amount.'</div>');
      }else {
        exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;กรุณาติดต่อแอดมิน</div>');
      }
    }else {
		exit('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i>&nbsp;Error Contact #Nattawut Phoobunlap</div>');
	}
}
?>
<html lang="th">
<head>
 <meta charset="utf-8">
 <meta name="description" content="Home | iTORKUNGz">
 <meta name="keywords" content="อิไตร๊ๆๆ">
 <meta name="author" content="iTORKUNGz">
 <title>Home | iTORKUNGz</title>
 <link rel="icon" href="favicon.ico">
 <link rel="stylesheet" href="dist/bootstrap.min.css">
 <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Kanit:300">
 <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css">
 <script src="dist/jquery.min.js"></script>
 <script src="dist/bootstrap.min.js"></script>
 <script src="dist/script.js"></script>
 <style type="text/css">
 body
 {
     background: url(dist/background.jpg?v=1.0) no-repeat center fixed;
   -webkit-background-size: cover;
   -moz-background-size: cover;
   -o-background-size: cover;
   background-size: cover;
   font-family:"Kanit", sans-serif;
   color: black;
 }
 </style>
</head>
<body>
  <div class="container col-4" style="margin-top:200px;">
      <div align="center">
      <div class="border border-dark" style="margin-top:40px; margin-bottom: 50px; ">
     <div class="card-header" style="color:black; background: rgba(255,255,255,0.3) !important;"><h5><i class="fas fa-credit-card"></i>&nbsp;ระบบ เติมเงินมายคราฟอัตโนมัต</h5></div>
       <div class="card-body" style="background: rgba(255,255,255,0.2) !important;">
       <div id="return"></div>
       <hr class="bg-light">
       <p>
       </p>
       <input type="hidden" name="act" value="">

       <div class="text-left mt-1 text-dark"><i class="fa fa-angle-right"></i> ชื่อตัวละคร</div>
      <div class="input-group" style="margin-bottom: 10px;">
      <input class="form-control" style="height: 40px;" name="username" id="username" type="username" placeholder="กรุณากรอกชื่อตัวละคร">
      </div>
       <div class="text-left mt-1 text-dark"><i class="fa fa-angle-right"></i> รหัสบัตร</div>
      <div class="input-group">
      <input class="form-control" style="height: 40px;" name="truemoney" id="truemoney" type="text" placeholder="รหัสบัตรทรูมันนี่ 14 หลัก">
      </div>
	<table class="table-success border border-secondary" style="width: 100%;border: 1px solid #fff;margin-top:10px;">
			<thead class="text-center">
				<tr style="border-bottom: 1px solid #fff">
					<th style="width: 50%;background: #16D39A;color: white;padding: 10px;border-width: 0.1px 0.1px 0.1px 0.0px;">TrueMoney</th>
					<th style="width: 50%;background: #16D39A;color: white;padding: 10px;border-width: 0.1px 0.1px 0.1px 0.0px;">Point</th>
				</tr>
			</thead>
			<tbody class="text-center">
				<tr>
					<td style="text-align:center">50 ทรู</td>
					<td><?php echo 50 * $config['promotion']; ?> พ้อย</td>
				</tr>
				<tr>
					<td style="text-align:center">90 ทรู</td>
					<td><?php echo 90 * $config['promotion']; ?> พ้อย</td>
				</tr>
				<tr>
					<td style="text-align:center">150 ทรู</td>
					<td><?php echo 150 * $config['promotion']; ?> พ้อย</td>
				</tr>
				<tr>
					<td style="text-align:center">300 ทรู</td>
					<td><?php echo 300 * $config['promotion']; ?> พ้อย</td>
				</tr>
				<tr>
					<td style="text-align:center">500 ทรู</td>
					<td><?php echo 500 * $config['promotion']; ?> พ้อย</td>
				</tr>
				<tr>
					<td style="text-align:center">1,000 ทรู</td>
					<td><?php echo 1000 * $config['promotion']; ?> พ้อย</td>
				</tr>
			</tbody>
		</table>
        <div style="margin-top:10px;" class="alert alert-warning"><i class="fa fa-exclamation-triangle fa-lg"></i> <b>คำเตือน</b> กรุณาออนไลน์ในเซิร์ฟเวอร์ด้วยนะครับ</div>
    <br>
      <button class="btn btn-primary btn-block" type="submit" id="btn" onclick="topup();"><i class="fas fa-sign-in-alt"></i> ยืนยันการเติมเงิน</button>
       </div>
     </div>
    </div>
  </div>
     <footer class="mastfoot mt-auto">
    <div class="text-center mt-5 mb-5" style="color: white;">2018  &copy; | iTORKUNGz</div>
   </footer>
</body>
</html>
