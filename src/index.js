import Editoria11yPreviewButton from "./components/Editoria11yPreviewButton.vue";
import Editoria11yView from "./components/Editoria11yView.vue";

panel.plugin("medienbaecker/editoria11y", {
	components: {
		"k-editoria11y-preview-button": Editoria11yPreviewButton,
		"k-editoria11y-view": Editoria11yView,
	},
});
