<?php
declare(strict_types=1);
session_start();

unset(
  $_SESSION["hid_staff_id"],
  $_SESSION["hid_staff_role"],
  $_SESSION["hid_staff_name"],
  $_SESSION["hid_staff_email"]
);

header("Location: index.php");
exit;
