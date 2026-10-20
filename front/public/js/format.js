// mise en forme des donnees de l'api pour l'affichage (francais)

// "2027-06-11" -> Date locale (sans decalage de fuseau)
function versDate(iso) {
    const [annee, mois, jour] = String(iso).split('-').map(Number);
    return new Date(annee, mois - 1, jour);
}

// 3000 (centimes) -> "30,00 €"
export function prix(centimes) {
    return (Number(centimes) / 100).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
}

// "2027-06-04" -> "ven. 04/06"
export function dateCourte(iso) {
    const date = versDate(iso);
    const jourSemaine = date.toLocaleDateString('fr-FR', { weekday: 'short' });
    const jj = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    return `${jourSemaine} ${jj}/${mm}`;
}

// "2027-06-11" -> "vendredi 11 juin 2027"
export function dateLongue(iso) {
    return versDate(iso).toLocaleDateString('fr-FR', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

// 1350 -> "1 350"
export function nombre(n) {
    return Number(n).toLocaleString('fr-FR');
}

// pluriel simple : pluriel(2, 'spectacle') -> "spectacles"
export function pluriel(n, mot, motPluriel = mot + 's') {
    return Number(n) > 1 ? motPluriel : mot;
}
