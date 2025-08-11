import './bootstrap';
import { Fancybox } from "@fancyapps/ui";
import "@fancyapps/ui/dist/fancybox/fancybox.css";

// Initialize fancybox
Fancybox.bind("[data-fancybox]", {
    // Configure default options
    Thumbs: {
        autoStart: false,
    },
    Toolbar: {
        display: {
            left: ["infobar"],
            middle: [],
            right: ["zoom", "slideshow", "fullscreen", "download", "thumbs", "close"],
        },
    },
    groupAll: true
});
