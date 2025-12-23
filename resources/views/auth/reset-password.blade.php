<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <form method="POST" action="{{ url('/api/user/password/reset/submit') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <input type="password" name="new_password" placeholder="Password baru">
        <button type="submit">Reset Password</button>
    </form>

</body>

</html>
