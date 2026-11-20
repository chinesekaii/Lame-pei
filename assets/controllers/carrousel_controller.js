import { Controller } from '@hotwired/stimulus';

/*
 * Carrousel horizontal : la piste défile (glissement au doigt, molette, flèches),
 * les points indiquent la position et permettent d'aller à une photo.
 */
export default class extends Controller {
    static targets = ['piste', 'precedent', 'suivant', 'point'];

    connect() {
        this.majEtat = this.majEtat.bind(this);
        this.pisteTarget.addEventListener('scroll', this.majEtat, { passive: true });
        window.addEventListener('resize', this.majEtat);
        this.majEtat();
    }

    disconnect() {
        this.pisteTarget.removeEventListener('scroll', this.majEtat);
        window.removeEventListener('resize', this.majEtat);
    }

    precedent() {
        this.pisteTarget.scrollBy({ left: -this.pas(), behavior: 'smooth' });
    }

    suivant() {
        this.pisteTarget.scrollBy({ left: this.pas(), behavior: 'smooth' });
    }

    aller({ params: { index } }) {
        const element = this.elements()[index];
        if (element) {
            this.pisteTarget.scrollTo({ left: element.offsetLeft - this.pisteTarget.offsetLeft, behavior: 'smooth' });
        }
    }

    // Largeur d'une photo + espacement : on avance d'une photo à la fois
    pas() {
        const [premier, second] = this.elements();
        if (!premier) return this.pisteTarget.clientWidth;
        return second ? second.offsetLeft - premier.offsetLeft : premier.offsetWidth;
    }

    elements() {
        return Array.from(this.pisteTarget.children);
    }

    majEtat() {
        const piste = this.pisteTarget;
        const fin = piste.scrollWidth - piste.clientWidth - 2;
        if (this.hasPrecedentTarget) this.precedentTarget.disabled = piste.scrollLeft <= 2;
        if (this.hasSuivantTarget) this.suivantTarget.disabled = piste.scrollLeft >= fin;

        // Un point par position possible : avec 3 photos visibles sur 7, il y a 5 positions
        const pas = this.pas() || 1;
        const visibles = Math.max(1, Math.round(piste.clientWidth / pas));
        const positions = Math.max(1, this.elements().length - visibles + 1);
        const actif = Math.min(positions - 1, Math.round(piste.scrollLeft / pas));
        this.pointTargets.forEach((point, i) => {
            point.hidden = i >= positions;
            point.classList.toggle('carrousel__point--actif', i === actif);
            point.setAttribute('aria-current', i === actif ? 'true' : 'false');
        });
    }
}
