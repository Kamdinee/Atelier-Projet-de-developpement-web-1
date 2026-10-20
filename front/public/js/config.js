// adresse de l'api utilisee par toutes les pages
// par defaut : meme machine que le front, port 8080 (marche en local et sur docketu)
// si API_PORT a ete change dans le .env, mettre le meme port ici
const API_PORT = 8080;

window.NRV_CONFIG = {
    apiUrl: `${window.location.protocol}//${window.location.hostname}:${API_PORT}`,
};
