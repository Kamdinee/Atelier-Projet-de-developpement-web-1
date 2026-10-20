// chargement et compilation des templates handlebars (public/templates/*.hbs)
// Handlebars est charge en global par vendor/handlebars.min.js

import { dateCourte, dateLongue, nombre, pluriel, prix } from './format.js';

const Handlebars = window.Handlebars;
const cache = new Map();

// helpers utilisables dans tous les templates
Handlebars.registerHelper('prix', (centimes) => prix(centimes));
Handlebars.registerHelper('dateCourte', (iso) => dateCourte(iso));
Handlebars.registerHelper('dateLongue', (iso) => dateLongue(iso));
Handlebars.registerHelper('nombre', (n) => nombre(n));
Handlebars.registerHelper('pluriel', (n, mot, motPluriel) =>
    pluriel(n, mot, typeof motPluriel === 'string' ? motPluriel : undefined));
Handlebars.registerHelper('joindre', (liste, separateur) =>
    (Array.isArray(liste) ? liste : []).join(typeof separateur === 'string' ? separateur : ', '));
Handlebars.registerHelper('eq', (a, b) => a === b);
Handlebars.registerHelper('plusDeUn', (liste) => Array.isArray(liste) && liste.length > 1);

async function chargerSource(nom) {
    const reponse = await fetch(`templates/${nom}.hbs`);
    if (!reponse.ok) {
        throw new Error(`template introuvable : ${nom}`);
    }
    return reponse.text();
}

// renvoie la fonction compilee du template (une seule requete par template)
export function template(nom) {
    if (!cache.has(nom)) {
        cache.set(nom, chargerSource(nom).then((source) => Handlebars.compile(source)));
    }
    return cache.get(nom);
}

// enregistre des templates comme partials : {{> carte-spectacle}}
export async function partials(...noms) {
    await Promise.all(noms.map(async (nom) => {
        Handlebars.registerPartial(nom, await chargerSource(nom));
    }));
}

// remplit un element avec le rendu d'un template
export async function afficher(element, nom, donnees) {
    const rendu = await template(nom);
    element.innerHTML = rendu(donnees);
}
