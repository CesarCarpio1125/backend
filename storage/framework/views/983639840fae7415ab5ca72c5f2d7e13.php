<!DOCTYPE html>
<html>
<head>
    <title><?php echo e($subject); ?></title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; color: #333333; background-color: #f4f4f4;">
    <div style="max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <!-- Header -->
        <div style="background: #2d3748; padding: 20px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">Innosure</h1>
        </div>

        <!-- Content -->
        <div style="padding: 30px;">
            <h2 style="color: #2d3748; margin-top: 0; font-size: 20px;"><?php echo e($subject); ?></h2>

            <div style="line-height: 1.6; color: #4a5568; margin-bottom: 25px; white-space: pre-line;">
                <?php if(is_string($message) || is_numeric($message)): ?>
                    <?php echo nl2br(e($message)); ?>

                <?php elseif(is_object($message) && method_exists($message, '__toString')): ?>
                    <?php echo nl2br(e($message->__toString())); ?>

                <?php else: ?>
                    <?php echo nl2br(e('No message content available.')); ?>

                <?php endif; ?>
            </div>

            <?php if(isset($pdfUrl) && $pdfUrl): ?>
                <div style="text-align: center; margin: 30px 0;">
                    <a href="<?php echo e($pdfUrl); ?>"
                       style="display: inline-block; background-color: #4299e1; color: #ffffff;
                              text-decoration: none; padding: 12px 24px; border-radius: 4px;
                              font-weight: bold; font-size: 16px;">
                        Ver informe completo
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</body>
</html>
<?php /**PATH /Users/elias/Herd/innoCalculator1/backend/resources/views/emails/informe.blade.php ENDPATH**/ ?>