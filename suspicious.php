<?php
/*
<subject>##service## - <label>summary.suspiciouslogin</label> ##account##</subject>
<body>
<label>summary.hello</label><br />
<br />
<label>summary.loginprevented</label><br />
<br />
<table>
<tr><td><label>summary.ip</label>:</td><td>##IP##</td></tr>
<tr><td><label>summary.country</label>:</td><td>##country##</td></tr>
<tr><td><label>summary.region</label>:</td><td>##region##</td></tr>
<tr><td><label>summary.city</label>:</td><td>##city##</td></tr>
<tr><td>&nbsp;</td><td>##maps##</td></tr>
</table><br />
<br />
<label>summary.advice</label><br />
<br />
<label>summary.regards</label><br />
##sender##<br />
</body>
*/
header("HTTP/1.1 401 Unauthorized");
die('Access denied');
?>