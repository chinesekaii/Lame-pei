import { Controller } from '@hotwired/stimulus';

/*
 * Ouvre et ferme le tiroir du panier.
 * Usage : data-controller="panier" sur <body>, data-action="panier#ouvrir" sur le bouton.
 */
export default class extends Controller {
    static targets = ['tiroir', 'overlay', 'compteur'];

    connect() {
        this.toucheEchap = (event) => {
            if (event.key === 'Escape') this.fermer();
        };
        document.addEventListener('keydown', this.toucheEchap);
    }

    disconnect() {
        document.removeEventListener('keydown', this.toucheEchap);
    }

    ouvrir() {
        this.overlayTarget.hidden = false;
        this.tiroirTarget.classList.add('panier--ouvert');
        this.tiroirTarget.setAttribute('aria-hidden', 'false');
    }

    fermer() {
        this.overlayTarget.hidden = true;
        this.tiroirTarget.classList.remove('panier--ouvert');
        this.tiroirTarget.setAttribute('aria-hidden', 'true');
    }
}
