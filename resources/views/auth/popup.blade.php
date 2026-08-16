<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pony.fm</title>
</head>
<body>
    <script>
        if (window.opener) {
            window.opener.postMessage({ type: 'pfm:login-complete', success: @js($success) }, window.location.origin);
            window.close();
        } else {
            window.location.replace('/');
        }
    </script>
    <p style="font-family: sans-serif;">You can close this window.</p>
</body>
</html>
