import { Controller } from '@hotwired/stimulus';

/*
 * Traînée de petites fleurs qui suit la souris (décoratif).
 *
 * - Uniquement avec une vraie souris (pas sur téléphone ni tablette).
 * - Désactivée si l'appareil demande moins d'animations (prefers-reduced-motion).
 * - Une fleur tous les ESPACEMENT pixels parcourus, MAX_FLEURS à l'écran au maximum :
 *   les fleurs sont de simples éléments animés en CSS puis supprimés.
 */
const COULEURS = ['#D8462A', '#A9502E', '#EE8A73', '#C79A3E', '#E58AAE'];
const ESPACEMENT = 28;
const MAX_FLEURS = 24;
const DUREE = 900; // ms, identique à l'animation CSS « fleur-trainee »

export default class extends Controller {
    connect() {
        const sourisPrecise = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        const moinsDAnimations = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!sourisPrecise || moinsDAnimations) {
            return;
        }

        this.derniere = null;
        this.enCours = 0;
        this.surMouvement = this.surMouvement.bind(this);
        document.addEventListener('mousemove', this.surMouvement, { passive: true });
    }

    disconnect() {
        document.removeEventListener('mousemove', this.surMouvement);
    }

    surMouvement(event) {
        const { clientX: x, clientY: y } = event;

        if (this.derniere) {
            const distance = Math.hypot(x - this.derniere.x, y - this.derniere.y);
            if (distance < ESPACEMENT) return;
        }
        this.derniere = { x, y };

        if (this.enCours >= MAX_FLEURS) return;
        this.semer(x, y);
    }

    semer(x, y) {
        const fleur = document.createElement('span');
        fleur.className = 'fleur-trainee';
        fleur.setAttribute('aria-hidden', 'true');
        fleur.textContent = '✿';

        const taille = 10 + Math.random() * 9;
        fleur.style.left = `${x}px`;
        fleur.style.top = `${y}px`;
        fleur.style.fontSize = `${taille}px`;
        fleur.style.color = COULEURS[Math.floor(Math.random() * COULEURS.length)];
        // Valeurs lues par l'animation CSS : légère dérive et rotation différentes pour chaque fleur
        fleur.style.setProperty('--derive', `${(Math.random() - 0.5) * 30}px`);
        fleur.style.setProperty('--rotation', `${(Math.random() - 0.5) * 240}deg`);

        document.body.appendChild(fleur);
        this.enCours++;

        setTimeout(() => {
            fleur.remove();
            this.enCours--;
        }, DUREE);
    }
}
