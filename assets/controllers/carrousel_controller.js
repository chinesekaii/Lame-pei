import { Controller } from '@hotwired/stimulus';

/*
 * Carrousel horizontal : la piste défile toute seule (une photo toutes les « delai » ms,
 * retour au début à la fin), et reste utilisable à la main (flèches, points, glissement).
 *
 * Le défilement automatique se met en pause quand on survole ou touche le carrousel,
 * quand une photo est agrandie ou quand l'onglet n'est pas visible.
 * Il ne démarre pas si l'appareil demande moins d'animations (prefers-reduced-motion).
 * Le bouton pause / lecture permet de l'arrêter (règle d'accessibilité WCAG 2.2.2).
 */
export default class extends Controller {
    static targets = ['piste', 'precedent', 'suivant', 'point', 'lecture'];
    static values = { delai: { type: Number, default: 2500 } };

    connect() {
        this.majEtat = this.majEtat.bind(this);
        this.surVisibilite = () => (document.hidden ? this.suspendre() : this.reprendre());

        this.pisteTarget.addEventListener('scroll', this.majEtat, { passive: true });
        window.addEventListener('resize', this.majEtat);
        document.addEventListener('visibilitychange', this.surVisibilite);

        // Pauses temporaires : survol, doigt posé, clavier dans le carrousel
        this.element.addEventListener('mouseenter', this.suspendre);
        this.element.addEventListener('mouseleave', this.reprendre);
        this.element.addEventListener('touchstart', this.suspendre, { passive: true });
        this.element.addEventListener('focusin', this.suspendre);
        this.element.addEventListener('focusout', this.reprendre);

        this.arreteParVisiteur = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        this.majEtat();
        this.majBoutonLecture();
        this.reprendre();
    }

    disconnect() {
        this.suspendre();
        this.pisteTarget.removeEventListener('scroll', this.majEtat);
        window.removeEventListener('resize', this.majEtat);
        document.removeEventListener('visibilitychange', this.surVisibilite);
    }

    // ---------- Défilement automatique ----------

    suspendre = () => {
        clearInterval(this.minuteur);
        this.minuteur = null;
    };

    reprendre = () => {
        if (this.arreteParVisiteur || this.minuteur || document.hidden || this.photoAgrandie()) {
            return;
        }
        this.minuteur = setInterval(() => this.avancer(), this.delaiValue);
    };

    /** Bouton ⏸ / ▶ : arrête ou relance le défilement automatique. */
    basculerLecture() {
        this.arreteParVisiteur = !this.arreteParVisiteur;
        if (this.arreteParVisiteur) {
            this.suspendre();
        } else {
            this.avancer();
        }
        this.majBoutonLecture();
    }

    avancer() {
        if (this.photoAgrandie()) return;
        const piste = this.pisteTarget;
        const aLaFin = piste.scrollLeft >= piste.scrollWidth - piste.clientWidth - 2;
        piste.scrollTo({ left: aLaFin ? 0 : piste.scrollLeft + this.pas(), behavior: 'smooth' });
    }

    photoAgrandie() {
        return this.element.querySelector('dialog[open]') !== null;
    }

    majBoutonLecture() {
        if (!this.hasLectureTarget) return;
        this.lectureTarget.textContent = this.arreteParVisiteur ? '▶' : '❚❚';
        this.lectureTarget.setAttribute('aria-label', this.arreteParVisiteur ? 'Lancer le défilement automatique' : 'Arrêter le défilement automatique');
        this.lectureTarget.setAttribute('aria-pressed', this.arreteParVisiteur ? 'true' : 'false');
    }

    // ---------- Navigation manuelle ----------

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
