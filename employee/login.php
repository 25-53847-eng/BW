<?php
// Unified login - redirect to main login page
// Employee and Admin use the same login page now
header("Location: ../login.php", true, 301);
exit;
