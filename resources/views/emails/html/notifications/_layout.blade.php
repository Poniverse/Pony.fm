<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>Pony.fm</title>
    <style>
        /* Client resets. Everything visual is inlined on the elements;
           this block only covers what inline styles can't reach. */
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; }
        img { border: 0; outline: none; text-decoration: none; }
        a { color: #84528a; }

        /* Dark mode for clients that honour it (Apple Mail, some others).
           Colours mirror the app's dark theme tokens. */
        @media (prefers-color-scheme: dark) {
            .email-bg { background-color: #0e1014 !important; }
            .email-card { background-color: #1a1d23 !important; border-color: #383d47 !important; }
            .text-heading { color: #f2f3f6 !important; }
            .text-body { color: #d5d9e0 !important; }
            .text-muted { color: #9aa1ad !important; }
            .text-faint { color: #6d747f !important; }
            .quote-block { background-color: #22262d !important; }
            .wordmark { color: #c9a5cd !important; }
            a { color: #c9a5cd; }
        }
    </style>
</head>
<body class="email-bg" style="margin: 0; padding: 0; background-color: #f3f4f7;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="email-bg" style="background-color: #f3f4f7;">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; width: 100%;">

                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding: 8px 0 20px;">
                            <a href="{{ config('app.url') }}" target="_blank" class="wordmark"
                               style="font-family: 'Josefin Sans', 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 26px; font-weight: 600; letter-spacing: 0.5px; color: #84528a; text-decoration: none;">
                                <img src="{{ $message->embed(public_path('images/email/ponyfm-logo.png')) }}" alt="Pony.fm" width="148" height="48"
                                     style="display: block; width: 148px; height: 48px;">
                            </a>
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td class="email-card" style="background-color: #ffffff; border: 1px solid #e1e4ea; border-radius: 12px; padding: 32px 32px 28px;">
                            @yield('content')
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td style="padding: 24px 8px 0;">
                            <p class="text-muted" style="margin: 0 0 12px; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 20px; color: #5d646f;">
                                You're getting this email because of your notification settings on Pony.fm.
                                You can change them in your <a href="{{ $accountSettingsUrl }}" target="_blank" style="color: #84528a;">account settings</a>
                                or <a href="{{ $unsubscribeUrl }}" target="_blank" style="color: #84528a;">unsubscribe</a> from this kind of email.
                            </p>
                            <p class="text-muted" style="margin: 0 0 12px; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; line-height: 20px; color: #5d646f;">
                                Any questions? Reply to this email or hit us up at
                                <a href="mailto:{{ $replyEmailAddress }}" style="color: #84528a;">{{ $replyEmailAddress }}</a>!
                            </p>
                            <p class="text-faint" style="margin: 0; font-family: 'Karla', 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; line-height: 18px; color: #7d848f;">
                                Sent with &hearts; to {{ $recipientName }}<br>
                                &copy; {{ $currentYear }} Pony.fm, a Poniverse project<br>
                                248-1641 Lonsdale Avenue, North Vancouver, BC V7M 2J5, Canada
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
