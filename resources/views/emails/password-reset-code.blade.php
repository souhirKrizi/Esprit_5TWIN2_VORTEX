<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Votre code de réinitialisation</title>
</head>
<body style="margin:0;padding:0;background:#f8f9ff;font-family:Inter,Arial,Helvetica,sans-serif;color:#121c29;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8f9ff;padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(17,29,53,0.08);">
          <tr>
            <td style="background:#111d35;padding:24px 32px;">
              <span style="font-size:20px;font-weight:700;color:#ffffff;">HeatAlert</span><br>
              <span style="font-size:11px;letter-spacing:1px;text-transform:uppercase;color:#bbc6e6;">Météorologie civique · Tunisie</span>
            </td>
          </tr>
          <tr>
            <td style="padding:32px;">
              <p style="margin:0 0 16px;font-size:16px;">Bonjour {{ $name }},</p>
              <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#45464d;">
                Vous avez demandé la réinitialisation du mot de passe de votre compte HeatAlert.
                Saisissez le code ci-dessous pour choisir un nouveau mot de passe&nbsp;:
              </p>
              <div style="text-align:center;margin:0 0 24px;">
                <span style="display:inline-block;padding:16px 28px;border-radius:12px;background:#fff1ea;border:1px solid #ffdbcc;font-size:32px;font-weight:700;letter-spacing:10px;color:#a14000;">{{ $code }}</span>
              </div>
              <p style="margin:0 0 8px;font-size:14px;color:#45464d;">Ce code est valable <strong>{{ $ttlMinutes }} minutes</strong>.</p>
              <p style="margin:0;font-size:14px;color:#45464d;">Si vous n’êtes pas à l’origine de cette demande, ignorez simplement cet e-mail&nbsp;: votre mot de passe reste inchangé.</p>
            </td>
          </tr>
          <tr>
            <td style="padding:16px 32px;background:#eff4ff;font-size:12px;color:#75777e;">
              © HeatAlert · République Tunisienne. Ne partagez jamais ce code.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
