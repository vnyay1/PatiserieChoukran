<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Images téléversées (produits, catégories, logos) : une photo de téléphone de plusieurs Mo
 * est redimensionnée et convertie en WebP avant d'être servie à tous les visiteurs.
 * Si GD ne peut pas la lire (format inattendu, image trop grande pour la mémoire),
 * le fichier d'origine est conservé tel quel.
 */
class Images
{
    private const QUALITE_WEBP = 80;

    public static function enregistrer(UploadedFile $fichier, string $dossier, int $largeurMax = 1200): string
    {
        $image = self::charger($fichier);

        if (! $image) {
            return self::conserverOriginal($fichier, $dossier);
        }

        $image = self::redimensionner($image, $largeurMax);

        ob_start();
        $ok = imagewebp($image, null, self::QUALITE_WEBP);
        $contenu = ob_get_clean();
        imagedestroy($image);

        if (! $ok || $contenu === '' || $contenu === false) {
            return self::conserverOriginal($fichier, $dossier);
        }

        $chemin = $dossier.'/'.Str::random(40).'.webp';
        if (! Storage::disk('public')->put($chemin, $contenu)) {
            throw self::echecEcriture($dossier);
        }

        return $chemin;
    }

    private static function conserverOriginal(UploadedFile $fichier, string $dossier): string
    {
        return $fichier->store($dossier, 'public') ?: throw self::echecEcriture($dossier);
    }

    // Le disque ne lève pas d'exception (throw => false) : sans ce contrôle, la base
    // enregistrerait le chemin d'un fichier qui n'existe pas
    private static function echecEcriture(string $dossier): \RuntimeException
    {
        return new \RuntimeException("Impossible d'enregistrer l'image dans storage/app/public/{$dossier} (droits d'écriture ?).");
    }

    private static function charger(UploadedFile $fichier): ?\GdImage
    {
        if (! function_exists('imagewebp')) {
            return null;
        }

        $infos = @getimagesize($fichier->getRealPath());
        if (! $infos || ! in_array($infos[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        // Environ 5 octets par pixel pendant le décodage : on n'épuise pas la mémoire de PHP
        if (self::memoireDisponible() < $infos[0] * $infos[1] * 5) {
            return null;
        }

        $image = @imagecreatefromstring((string) file_get_contents($fichier->getRealPath()));
        if (! $image) {
            return null;
        }

        if ($infos[2] === IMAGETYPE_JPEG) {
            $image = self::orienter($image, $fichier->getRealPath());
        }

        return $image;
    }

    private static function redimensionner(\GdImage $image, int $largeurMax): \GdImage
    {
        $largeur = imagesx($image);
        $hauteur = imagesy($image);

        // Côté le plus long limité à $largeurMax
        $ratio = $largeurMax / max($largeur, $hauteur);
        if ($ratio >= 1) {
            imagepalettetotruecolor($image);
            imagesavealpha($image, true);

            return $image;
        }

        $nouvelleLargeur = max(1, (int) round($largeur * $ratio));
        $nouvelleHauteur = max(1, (int) round($hauteur * $ratio));

        $copie = imagecreatetruecolor($nouvelleLargeur, $nouvelleHauteur);
        imagealphablending($copie, false);
        imagesavealpha($copie, true);
        imagecopyresampled($copie, $image, 0, 0, 0, 0, $nouvelleLargeur, $nouvelleHauteur, $largeur, $hauteur);
        imagedestroy($image);

        return $copie;
    }

    // Les photos de téléphone portent leur rotation dans l'EXIF, que GD ignore
    private static function orienter(\GdImage $image, string $chemin): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $angle = match ((int) (@exif_read_data($chemin)['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $tournee = imagerotate($image, $angle, 0);
        if (! $tournee) {
            return $image;
        }
        imagedestroy($image);

        return $tournee;
    }

    private static function memoireDisponible(): int
    {
        $limite = ini_get('memory_limit');
        if ($limite === false || $limite === '' || (int) $limite <= 0) {
            return PHP_INT_MAX;
        }

        $octets = (int) $limite * match (strtolower(substr($limite, -1))) {
            'g' => 1024 ** 3,
            'm' => 1024 ** 2,
            'k' => 1024,
            default => 1,
        };

        return max(0, $octets - memory_get_usage());
    }
}
