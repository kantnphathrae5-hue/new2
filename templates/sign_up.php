<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up to YourApp</title>
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

<body class="flex flex-col items-center pt-8 px-4">



    <h1 class="text-2xl font-light mb-4">Create your account</h1>

    <div class="bg-white border border-[#d8dee4] rounded-lg p-5 w-full max-w-[340px] shadow-sm">
        <form action="/entrypj/routes/User.php?url=User" method="POST" class="space-y-4">
            <input type="hidden" name="action" value="register">

            <div>
                <label class="block text-sm font-normal mb-2 text-slate-900">ชื่อ-นามสกุล</label>
                <input type="text" name="name" class="btn-input w-full px-3 py-1.5 text-sm" placeholder="Full name" required>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-normal mb-2 text-slate-900">เพศ</label>
                    <select name="gender" class="btn-input w-full px-3 py-1.5 text-sm bg-white cursor-pointer">
                        <option value="Male">ชาย</option>
                        <option value="Female">หญิง</option>
                        <option value="Other">อื่นๆ</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-normal mb-2 text-slate-900">วันเกิด</label>
                    <input type="date" name="birthdate" class="btn-input w-full px-3 py-1.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-normal mb-2 text-slate-900">จังหวัด</label>
                <input type="text" name="province" class="btn-input w-full px-3 py-1.5 text-sm" placeholder="Your province">
            </div>

            <div>
                <label class="block text-sm font-normal mb-2 text-slate-900">อีเมล</label>
                <input type="email" name="email" class="btn-input w-full px-3 py-1.5 text-sm" placeholder="Email address" required>
            </div>

            <div>
                <label class="block text-sm font-normal mb-2 text-slate-900">รหัสผ่าน</label>
                <input type="password" name="password" class="btn-input w-full px-3 py-1.5 text-sm" placeholder="Password" required>
            </div>

            <button type="submit" class="w-full bg-[#2da44e] hover:bg-[#2c974b] text-white font-semibold py-1.5 rounded-md text-sm mt-4 transition duration-200">
                สมัครสมาชิก
            </button>
        </form>
    </div>

    <div class="mt-4 border border-[#d8dee4] rounded-lg p-4 w-full max-w-[340px] text-center">
        <p class="text-sm">Already have an account? <a href="sign_in.php" class="text-[#0969da] hover:underline">Sign in</a>.</p>
    </div>

</body>

</html>