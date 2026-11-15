<?php

require_once __DIR__ . '/../config/session.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo isset($pageTitle)
            ? htmlspecialchars($pageTitle)
            : 'Froshgah - Online Shopping'; ?>
    </title>

    <link rel="stylesheet"
          href="/froshgah/assets/css/style.css">
          <link rel="stylesheet" href="/froshgah/assets/css/pages.css?v=1">
      <link rel="stylesheet" href="/froshgah/assets/css/products.css">
      <link rel="stylesheet" href="/froshgah/assets/css/product-details.css">
    <script src="/froshgah/assets/js/script.js"
            defer></script>

</head>

<body>