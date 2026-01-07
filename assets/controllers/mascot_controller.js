import { Controller } from '@hotwired/stimulus';
import lottie from 'lottie-web';

/*
 * This is an example Stimulus controller!
 *
 * Any element with a data-controller="mascot" attribute will cause
 * this controller to be executed. The name "mascot" comes from the filename:
 * mascot_controller.js -> "mascot"
 *
 * Delete this file or adapt it for your use!
 */
export default class extends Controller {
    static targets = ['container', 'bubble'];

    connect() {
        this.animation = lottie.loadAnimation({
            container: this.containerTarget,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            path: 'https://assets10.lottiefiles.com/packages/lf20_5njp3vgg.json' // Placeholder cute robot/character
        });

        this.sayHello();

        // Listen for custom events (e.g. from other controllers or flash messages)
        window.addEventListener('mascot:celebrate', this.celebrate.bind(this));
    }

    sayHello() {
        this.showBubble("Shalom ! Prêt pour une nouvelle mission ? 🚀");
        setTimeout(() => this.hideBubble(), 5000);
    }

    celebrate() {
        // Change animation speed or play a specific segment if supported
        this.animation.setSpeed(2);
        this.showBubble("Mazal Tov ! Tu es un champion ! 🏆");
        setTimeout(() => {
            this.animation.setSpeed(1);
            this.hideBubble();
        }, 4000);
    }

    showBubble(text) {
        this.bubbleTarget.textContent = text;
        this.bubbleTarget.classList.remove('hidden');
        this.bubbleTarget.classList.add('animate-bounce');
    }

    hideBubble() {
        this.bubbleTarget.classList.add('hidden');
        this.bubbleTarget.classList.remove('animate-bounce');
    }
}
