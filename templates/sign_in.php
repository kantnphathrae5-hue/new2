<?php
if (!empty($_SESSION['user_id'])) { 
    header("Location: /entrypj/templates/home.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="th">
    

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in to YourApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f6f8fa;
        }

        .btn-input {
            border: 1px solid #d0d7de;
            border-radius: 6px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .btn-input:focus {
            border-color: #0969da;
            outline: none;
            box-shadow: 0 0 0 3px rgba(9, 105, 218, 0.3);
        }
    </style>
</head>

<body class="flex flex-col items-center pt-12 px-4">



    <h1 class="text-2xl font-light mb-4">Sign in to YourApp</h1>

    <div class="bg-white border border-[#d8dee4] rounded-lg p-5 w-full max-w-[308px] shadow-sm">
        <form action="/entrypj/routes/User.php" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="login">

            <div>
                <label class="block text-sm font-normal mb-2 text-slate-900">Email address</label>
                <input type="email" name="email" class="btn-input w-full px-3 py-1.5 text-sm" required>
            </div>

            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="block text-sm font-normal text-slate-900">Password</label>
                    <a href="#" class="text-xs text-[#0969da] hover:underline">Forgot password?</a>
                </div>
                <input type="password" name="password" class="btn-input w-full px-3 py-1.5 text-sm" required>
            </div>

            <button type="submit" class="w-full bg-[#2da44e] hover:bg-[#2c974b] text-white font-semibold py-1.5 rounded-md text-sm mt-4 transition duration-200">
                Sign in
            </button>
        </form>
    </div>

    <div class="mt-4 border border-[#d8dee4] rounded-lg p-4 w-full max-w-[308px] text-center bg-transparent">
        <p class="text-sm">New to YourApp? <a href="sign_up.php" class="text-[#0969da] hover:underline">Create an account</a>.</p>
    </div>

</body>

</html>