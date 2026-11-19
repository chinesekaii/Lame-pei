import { Controller } from '@hotwired/stimulus';

/*
 * Galerie de la fiche produit : un clic sur une vignette l'affiche en grand.
 */
export default class extends Controller {
    static targets = ['principale', 'vignette'];

    afficher({ params: { src }, currentTarget }) {
        this.principaleTarget.src = src;
        this.vignetteTargets.forEach((v) => v.classList.toggle('galerie__vignette--active', v === currentTarget));
    }
}
