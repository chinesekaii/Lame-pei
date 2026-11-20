import { Controller } from '@hotwired/stimulus';

/*
 * Agrandit une photo de la galerie clients dans une fenêtre (<dialog>).
 * Échap ou clic sur le fond pour fermer (géré nativement par <dialog> pour Échap).
 */
export default class extends Controller {
    static targets = ['fenetre', 'image', 'legende'];

    ouvrir({ params: { src, legende } }) {
        this.imageTarget.src = src;
        this.imageTarget.alt = legende || 'Photo envoyée par une cliente';
        this.legendeTarget.textContent = legende || '';
        this.fenetreTarget.showModal();
    }

    fermer() {
        this.fenetreTarget.close();
    }

    // Un clic hors de la photo (sur le fond sombre) ferme la fenêtre
    fermerSiFond(event) {
        if (event.target === this.fenetreTarget) {
            this.fermer();
        }
    }
}
