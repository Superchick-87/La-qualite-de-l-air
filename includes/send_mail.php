<?php
// Définit la date utilisée pour nommer le PDF
$jour = date('d-m-Y');
$date = date('d-m-Y', strtotime($jour. ' + 1 days'));

setlocale(LC_TIME, 'fr_FR.UTF-8');

// Génération du PDF
try {
    require_once __DIR__ . '/pdf_3cols2M.php';
} catch (Throwable $e) {
    echo "❌ ERREUR PDF: " . $e->getMessage() . "\n";
}

// === Envoi du mail avec pièce jointe ===
$filename = 'Infog_Qualite_Air_' . $date . '.pdf';
$filepath = dirname(__DIR__)  . '/ProductionPdf/' . $filename;

$to = 'n.peyrebrune@sudouest.fr';
$from = 'n.peyrebrune@sudouest.fr';
$cc = 'f.sallet@sudouest.fr, infographies@sudouest.fr';
$subject = '🌳☀️ Infographie qualité de l\'air | Date de parution : ' . $date ;

if (file_exists($filepath)) {
    // Lire et encoder le fichier PDF
    $file_content = chunk_split(base64_encode(file_get_contents($filepath)));
    $boundary = md5(uniqid());

    // Construction des headers
    $headers = "From: $from\r\n";
    $headers .= "Cc: " . $cc . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    // Corps du message
    date_default_timezone_set('Europe/Paris');
    $message = "--$boundary\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= "<html><body>";
    $message .= "<p style='font-family: sans-serif;'>✅ Le PDF a été généré avec succès aujourd'hui à : <strong>" . date('H:i:s') . "</strong></p>";
    $message .= "<p>Vous trouverez le fichier PDF en pièce jointe.</p>";
    $message .= "</body></html>\r\n\r\n";

    // Pièce jointe
    $message .= "--$boundary\r\n";
    $message .= "Content-Type: application/pdf; name=\"$filename\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"$filename\"\r\n\r\n";
    $message .= $file_content . "\r\n";
    $message .= "--$boundary--";

    $mail_sent = mail($to, $subject, $message, $headers);
    echo "Mail avec pièce jointe " . ($mail_sent ? "envoyé" : "non envoyé") . ".\n";
} else {
    // Fichier non trouvé, envoie simple
    $message = "Échec de la génération du PDF à : " . date('Y-m-d H:i:s');
    $headers = 'From: ' . $from . "\r\n" .
               'Reply-To: ' . $from . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    mail($to, $subject, $message, $headers);
    echo $message . "\n";
}
