// qr code d'un billet en svg (bibliotheque qrcode-generator, licence mit, dans vendor/)
import qrcode from '../vendor/qrcode.js';

// renvoie le code svg d'un qr code qui encode le texte (l'url du billet)
export function svgQr(texte, libelle = 'QR code du billet') {
    const qr = qrcode(0, 'M');
    qr.addData(texte);
    qr.make();

    const taille = qr.getModuleCount();
    let modules = '';
    for (let ligne = 0; ligne < taille; ligne += 1) {
        for (let colonne = 0; colonne < taille; colonne += 1) {
            if (qr.isDark(ligne, colonne)) {
                modules += `M${colonne} ${ligne}h1v1h-1z`;
            }
        }
    }

    // marge de 2 modules autour du code, fond blanc pour la lecture
    const vue = `-2 -2 ${taille + 4} ${taille + 4}`;
    const titre = libelle.replace(/[<>&"]/g, '');
    return `<svg class="qr" xmlns="http://www.w3.org/2000/svg" viewBox="${vue}" role="img" aria-label="${titre}" shape-rendering="crispEdges">`
        + `<rect x="-2" y="-2" width="${taille + 4}" height="${taille + 4}" fill="#fff"/>`
        + `<path fill="#000" d="${modules}"/></svg>`;
}
