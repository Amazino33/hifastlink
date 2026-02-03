<div style="font-family: Arial, Helvetica, sans-serif;">
    <h2>Hello {{ $user->name }},</h2>
    <p>Click the link below to login. This link will expire in 30 minutes.</p>
    <p><a href="{{ $url }}">Login to Router & Site</a></p>
    <p>If you didn't request this link, ignore this message.</p>
</div>