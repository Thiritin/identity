<!DOCTYPE html>

<html lang="en">
<head>
    <title></title>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:PixelsPerInch>96</o:PixelsPerInch>
            <o:AllowPNG/>
        </o:OfficeDocumentSettings>
    </xml><![endif]-->
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: inherit !important;
        }

        #MessageViewBody a {
            color: inherit;
            text-decoration: none;
        }

        p {
            line-height: inherit
        }

        @media (max-width: 720px) {
            .icons-inner {
                text-align: center;
            }

            .icons-inner td {
                margin: 0 auto;
            }

            .row-content {
                width: 100% !important;
            }

            .mobile_hide {
                display: none;
            }

            .stack .column {
                width: 100%;
                display: block;
            }

            .mobile_hide {
                min-height: 0;
                max-height: 0;
                max-width: 0;
                overflow: hidden;
                font-size: 0px;
            }
        }
    </style>
</head>
<body style="background-color: #f3f4f6; margin: 0; padding: 0; -webkit-text-size-adjust: none; text-size-adjust: none;">
<table border="0" cellpadding="0" cellspacing="0" class="nl-container" role="presentation"
       style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f3f4f6;" width="100%">
    <tbody>
    <tr>
        <td>
            <table align="center" border="0" cellpadding="0" cellspacing="0" class="row row-1" role="presentation"
                   style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;" width="100%">
                <tbody>
                <tr>
                    <td>
                        <table align="center" border="0" cellpadding="0" cellspacing="0" class="row-content stack"
                               role="presentation"
                               style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; color: #000000; width: 700px;"
                               width="700">
                            <tbody>
                            <tr>
                                <td class="column"
                                    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;"
                                    width="16.666666666666668%">
                                    <div class="spacer_block" style="height:5px;line-height:5px;font-size:1px;"> </div>
                                    <table border="0" cellpadding="0" cellspacing="0" class="image_block mobile_hide"
                                           role="presentation" style="mso-table-lspace: 0pt; mso-table-rspace: 0pt;"
                                           width="100%">
                                        <tr>
                                            <td style="width:100%;padding-top:10px;padding-right:5px;padding-bottom:5px;padding-left:5px;">
                                                <div align="right" style="line-height:10px"><img
                                                        src="{{ asset('images/mail/vertical.png') }}"
                                                        style="display: block; height: auto; border: 0; width: 49px; max-width: 100%;"
                                                        width="49"/></div>
                                            </td>
                                        </tr>
                                    </table>
                                    <div class="spacer_block" style="height:5px;line-height:5px;font-size:1px;"> </div>
                                </td>
                                <td class="column"
                                    style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; font-weight: 400; text-align: left; vertical-align: top; background-color: #ffffff; padding-left: 10px; padding-right: 10px; border-top: 0px; border-right: 0px; border-bottom: 0px; border-left: 0px;"
                                    width="83.33333333333333%">
                                    {{ $header ?? '' }}
                                    <table border="0" cellpadding="10" cellspacing="0" class="text_block"
                                           role="presentation"
                                           style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;"
                                           width="100%">
                                        <tr>
                                            <td>
                                                <div style="font-family: sans-serif">
                                                    <div
                                                        style="font-size: 14px; mso-line-height-alt: 16.8px; color: #555555; line-height: 1.2; font-family: Arial, Helvetica Neue, Helvetica, sans-serif;">
                                                        <p style="margin: 0; font-size: 14px;">
                                                            {{ Illuminate\Mail\Markdown::parse($slot) }}
                                                            {{ $subcopy ?? '' }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                    {{ $footer ?? '' }}
                                    <table border="0" cellpadding="0" cellspacing="0" class="text_block"
                                           role="presentation"
                                           style="mso-table-lspace: 0pt; mso-table-rspace: 0pt; word-break: break-word;"
                                           width="100%">
                                        <tr>
                                            <td style="padding-right:10px;padding-bottom:20px;padding-left:10px;">
                                                <div style="font-family: sans-serif">
                                                    <div
                                                        style="font-size: 14px; mso-line-height-alt: 16.8px; color: #555555; line-height: 1.2; font-family: Arial, Helvetica Neue, Helvetica, sans-serif;">
                                                        <p dir="ltr"
                                                           style="margin: 0; font-size: 14px; text-align: left;"><span
                                                                style="font-size:12px;color:#8f8d8d;">Yours,<br/>Eurofurence Identity Team<br/><br/>Any help required? Shoot us an email to: <a
                                                                    href="mailto:identity@eurofurence.org">identity@eurofurence.org</a></span>
                                                        </p>
                                                        <p dir="ltr"
                                                           style="margin: 0; font-size: 14px; text-align: left; mso-line-height-alt: 16.8px;">
                                                             </p>
                                                        <p dir="ltr"
                                                           style="margin: 0; font-size: 14px; text-align: left;"><span
                                                                style="font-size:12px;color:#8f8d8d;">Eurofurence e.V. - Am Kielshof 21a - 51105 Köln</span><br/><span
                                                                style="font-size:12px;color:#8f8d8d;">Vereinsregister Nr. 19784, Amtsgericht Köln</span><br/><span
                                                                style="font-size:12px;color:#8f8d8d;">Umsatzsteueridentifikationsnummer DE219481694</span><br/><span
                                                                style="font-size:12px;color:#8f8d8d;">1. Vorsitzender: Sven Tegethoff</span><br/><br/><span
                                                                style="font-size:12px;color:#8f8d8d;">Legal information according to §5 TMG obtainable at <br><a
                                                                    href="http://www.eurofurence.de/index.php?impressum"
                                                                    rel="noopener" style="color:#8f8d8d;"
                                                                    target="_blank"
                                                                    title="http://www.eurofurence.de/index.php?impressum">http://www.eurofurence.de/index.php?impressum</a></span>
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table><!-- End -->
</body>
</html>
