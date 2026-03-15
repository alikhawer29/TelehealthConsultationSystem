<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Password Reset - Wellxa</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td {font-family: Arial, Helvetica, sans-serif !important;}
    </style>
    <![endif]-->
    <style type="text/css">
        @media only screen and (max-width: 600px) {
            .mobile-full-width {
                width: 100% !important;
            }

            .mobile-center {
                text-align: center !important;
            }

            .mobile-padding {
                padding: 15px !important;
            }
        }
    </style>
</head>

<body style="margin: 0; padding: 0; color: #333333; font-family: Arial, Helvetica, sans-serif; line-height: 1.6;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
        <tr>
            <td style="padding: 20px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" align="center"
                    style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 12px 83px #E9DEEF;">
                    <!-- Logo Section -->
                    <tr>
                        <td style="padding: 30px 30px 20px; text-align: center;">
                            <img src="{{ asset('images/logo.png') }}" alt="Wellxa Logo"
                                style="max-width: 180px; height: auto;">
                        </td>
                    </tr>

                    <!-- Content Section -->
                    <tr>
                        <td style="padding: 0 40px 20px; text-align: center;">
                            <p style="font-size: 18px; margin-bottom: 20px; color: #333;">Hi {{ $firstName }},</p>

                            <p style="margin-bottom: 20px; font-size: 16px; color: #555555; line-height: 1.5;">
                                We received a request to reset your Wellxa account password.
                            </p>

                            <p style="margin-bottom: 20px; font-size: 16px; color: #555555; line-height: 1.5;">
                                Please use the code below to verify your request:
                            </p>

                            <!-- OTP Code Section -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"
                                style="margin: 25px 0;">
                                <tr>
                                    <td style="padding: 0; text-align: center;">
                                        <ul style="padding-left: 20px; margin: 0; color: #555555;">
                                            <li style="margin-bottom: 15px; font-size: 16px; list-style: none;">
                                                <strong style="color: #333;">Password Reset Code:</strong>
                                                <span
                                                    style="font-size: 20px; font-weight: bold; color: #6c63ff; margin-left: 5px;">{{ $otp }}</span>
                                            </li>
                                        </ul>
                                        <p style="font-size: 14px; color: #888888; margin: 5px 0 0 20px;">
                                            This code is valid for 10 minutes.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Warning Message -->
                            <p
                                style="margin: 25px 0; font-size: 16px; color: #555555; line-height: 1.5;text-align: center;">
                                If you didn't request this, please ignore this email. Your account is safe.
                            </p>
                        </td>
                    </tr>

                    <!-- Signature Section -->
                    <tr>
                        <td style="padding: 10px 40px 30px; text-align: center;">
                            <p style="margin: 0; font-size: 16px; color: #333;">Take care,</p>
                            <p style="margin: 0; font-size: 16px; color: #555555;">- The <span
                                    style="color: #6c63ff; font-weight: 600;">Wellxa</span> Team</p>
                        </td>
                    </tr>

                    <!-- Social Media Icons -->
                    <tr>
                        <td style="padding: 0 40px 30px; text-align: center;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" align="center">
                                <tr>
                                    <td style="padding: 0 8px;">
                                        <a href="#" style="display: inline-block;">
                                            <img src="{{ asset('images/facebook.png') }}" alt="Facebook"
                                                style="width: 32px; height: 32px; border-radius: 50%;">
                                        </a>
                                    </td>
                                    <td style="padding: 0 8px;">
                                        <a href="#" style="display: inline-block;">
                                            <img src="{{ asset('images/x.png') }}" alt="Twitter"
                                                style="width: 32px; height: 32px; border-radius: 50%;">
                                        </a>
                                    </td>
                                    <td style="padding: 0 8px;">
                                        <a href="#" style="display: inline-block;">
                                            <img src="{{ asset('images/insta.png') }}" alt="Instagram"
                                                style="width: 32px; height: 32px; border-radius: 50%;">
                                        </a>
                                    </td>
                                    <td style="padding: 0 8px;">
                                        <a href="#" style="display: inline-block;">
                                            <img src="{{ asset('images/linkdin.png') }}" alt="LinkedIn"
                                                style="width: 32px; height: 32px; border-radius: 50%;">
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer Section -->
                    <tr>
                        <td
                            style="background-color: #f8f9fa; padding: 25px 40px; text-align: center; border-top: 1px solid #eeeeee;">
                            <img src="{{ asset('images/light-logo.png') }}" alt="Wellxa Logo"
                                style="max-width: 150px; height: auto; margin-bottom: 15px;">
                            <p style="margin: 5px 0; font-size: 14px; color: #888888;">
                                <a href="mailto:care@wellxa.ae"
                                    style="color: #666666; text-decoration: none;">care@wellxa.ae</a> |
                                <a href="https://www.wellxa.ae"
                                    style="color: #666666; text-decoration: none;">www.wellxa.ae</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
