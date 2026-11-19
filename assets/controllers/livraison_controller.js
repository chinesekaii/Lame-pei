import { Controller } from '@hotwired/stimulus';

/*
 * Page de commande : met à jour les frais de livraison et le total
 * dès que le client choisit sa commune (le tarif est lu dans data-frais de l'option).
 * Le calcul définitif est toujours refait côté serveur.
 */
export default class extends Controller {
    static targets = ['commune', 'frais', 'secteur', 'total', 'totalBouton'];
    static values = { sousTotal: Number };

    connect() {
        this.changer();
    }

    changer() {
        const option = this.communeTarget.selectedOptions[0];
        const frais = option && option.dataset.frais !== undefined ? parseInt(option.dataset.frais, 10) : null;

        if (frais === null || Number.isNaN(frais)) {
            this.fraisTarget.textContent = 'selon la commune';
            this.secteurTarget.textContent = '';
            this.afficherTotal(this.sousTotalValue);
            return;
        }

        this.fraisTarget.textContent = frais === 0 ? 'offerte' : this.formater(frais);
        this.secteurTarget.textContent = `(${option.dataset.secteur})`;
        this.afficherTotal(this.sousTotalValue + frais);
    }

    afficherTotal(centimes) {
        this.totalTarget.textContent = this.formater(centimes);
        this.totalBoutonTarget.textContent = this.formater(centimes);
    }

    // Même rendu que le filtre Twig |prix : « 45 € » ou « 45,50 € »
    formater(centimes) {
        const decimales = centimes % 100 === 0 ? 0 : 2;
        return (centimes / 100).toLocaleString('fr-FR', {
            minimumFractionDigits: decimales,
            maximumFractionDigits: decimales,
        }).replace(/\s/g, ' ') + ' €';
    }
}
