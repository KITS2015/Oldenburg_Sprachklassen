<?php
return [
  'transport' => 'smtp',
  'host'      => 'smtp.ionos.de',
  'port'      => 587,
  'username'  => 'no-reply@oldenburg.anmeldung.schule',
  'password'  => 'cvw453wgsvvew4!',
  'from'      => ['address' => 'no-reply@oldenburg.anmeldung.schule', 'name' => 'Bewerbung Sprachklasse'],
  'reply_to'  => ['address' => 'info@oldenburg.anmeldung.schule', 'name' => 'BBS Oldenburg'],
  'secure'    => 'STARTTLS',
  'debug'     => 0,
];
