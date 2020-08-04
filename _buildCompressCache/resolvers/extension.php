<?php

/** @var xPDOTransport $transport */
/** @var array $options */
/** @var modX $modx */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            if ($modx instanceof modX) {
                $modx->addExtensionPackage('compresscache', '[[++core_path]]components/compresscache');
                copy(
                    MODX_CORE_PATH . 'components/compresscache/compressfilecache.class.php',
                    MODX_CORE_PATH . 'xpdo/cache/compressfilecache.class.php'
                );
            }
            break;

        case xPDOTransport::ACTION_UNINSTALL:
            if ($modx instanceof modX) {
                $modx->removeExtensionPackage('compresscache');
                @unlink(MODX_CORE_PATH . 'xpdo/cache/compressfilecache.class.php');
                $modx->cacheManager->refresh();
            }
            break;
    }
}
return true;
