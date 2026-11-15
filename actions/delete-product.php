
foreach ($images as $path) {

    $prefix = 'uploads/products/';

    if (strpos($path, $prefix) === 0) {

        $fileName = substr($path, strlen($prefix));

        if ($fileName !== '' && basename($fileName) === $fileName) {

            $filePath = __DIR__ . '/../uploads/products/' . $fileName;

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
