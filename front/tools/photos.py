# prepare les photos des spectacles pour le site
# usage : python3 front/tools/photos.py <dossier des photos sources>
# le dossier source contient un sous-dossier par style (classic-rock, metal...) ;
# chaque photo est recadree en 16/10 et enregistree en webp en deux tailles :
#   public/img/spectacles/<style>-<n>.webp      1200 x 750 (ordinateur, ecrans haute densite)
#   public/img/spectacles/<style>-<n>-640.webp  640 x 400  (telephone)
# le n-ieme spectacle d'un style (dans l'ordre du programme) prend la n-ieme photo
import sys
from pathlib import Path
from PIL import Image, ImageOps

STYLES = ['classic-rock', 'blues-rock', 'metal', 'chanson', 'reggae', 'punk', 'folk', 'electro-rock']
SORTIE = Path(__file__).resolve().parent.parent / 'public' / 'img' / 'spectacles'
TAILLES = {'': (1200, 750), '-640': (640, 400)}
EXTENSIONS = {'.jpg', '.jpeg', '.png', '.webp'}


def recadrer(image, largeur, hauteur):
    # recadrage centre ; une photo en hauteur garde plutot le haut (visages, scene)
    centrage = (0.5, 0.35) if image.height > image.width else (0.5, 0.5)
    return ImageOps.fit(image, (largeur, hauteur), Image.Resampling.LANCZOS, centering=centrage)


def main(source):
    SORTIE.mkdir(parents=True, exist_ok=True)
    for ancien in SORTIE.iterdir():
        ancien.unlink()
    total = 0
    for style in STYLES:
        photos = sorted(p for p in (source / style).glob('*') if p.suffix.lower() in EXTENSIONS)
        for n, chemin in enumerate(photos, start=1):
            image = ImageOps.exif_transpose(Image.open(chemin)).convert('RGB')
            for suffixe, (largeur, hauteur) in TAILLES.items():
                recadrer(image, largeur, hauteur).save(SORTIE / f'{style}-{n}{suffixe}.webp', 'WEBP', quality=80, method=6)
            total += 1
        print(f'{style} : {len(photos)} photo(s)')
    print(total, 'photos dans', SORTIE)


if __name__ == '__main__':
    if len(sys.argv) != 2:
        sys.exit('usage : python3 front/tools/photos.py <dossier des photos sources>')
    main(Path(sys.argv[1]))
