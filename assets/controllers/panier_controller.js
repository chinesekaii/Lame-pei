import { Controller } from '@hotwired/stimulus';

/*
 * Ouvre et ferme le tiroir du panier.
 * Usage : data-controller="panier" sur <body>, data-action="panier#ouvrir" sur le bouton.
 * data-panier-ouvert-value="true" ouvre le tiroir au chargement (après un ajout).
 */
export default class extends Controller {
    static targets = ['tiroir', 'overlay'];
    static values = { ouvert: Boolean };

    connect() {
        this.toucheEchap = (event) => {
            if (event.key === 'Escape') this.fermer();
        };
        // Turbo garde une copie de la page pour le bouton « Retour » : on la garde fermée
        this.avantCache = () => this.fermer();

        document.addEventListener('keydown', this.toucheEchap);
        document.addEventListener('turbo:before-cache', this.avantCache);

        if (this.ouvertValue) {
            requestAnimationFrame(() => this.ouvrir());
        }
    }

    disconnect() {
        document.removeEventListener('keydown', this.toucheEchap);
        document.removeEventListener('turbo:before-cache', this.avantCache);
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
