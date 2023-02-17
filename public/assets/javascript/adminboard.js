function afficheListe(zone) {
    // fonctionne en mettant dans la balise button : onclick="afficheListe('participants')"
    let bouton = '#'+zone+'Bouton';
    if (document.getElementById(zone).hidden === true) {
        document.getElementById(zone).hidden = false;
        document.querySelector(bouton).classList.add('bg-colored');
    } else {
        document.getElementById(zone).hidden = true;
        document.querySelector(bouton).classList.remove('bg-colored');
    }
}
