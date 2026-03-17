<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Organiser Approved - Ticketly</title>
</head>
<body style="font-family:Inter,Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px;">
  <div style="max-width:600px;margin:0 auto;background:#111827;border-radius:12px;padding:24px;">
    <h2 style="color:#ffffff;margin-bottom:12px;">Your organiser account is approved</h2>
    <p style="color:#cbd5f5;font-size:14px;line-height:1.6;">
      Hi {{ $organiser->name }},<br><br>
      Great news! Your Ticketly organiser account has been approved. You can now sign in and start publishing events.
    </p>
    <p style="margin-top:20px;">
      <a href="{{ route('organiser.login') }}" style="color:#10b981;text-decoration:none;">Sign in to your organiser dashboard</a>
    </p>
  </div>
</body>
</html>
