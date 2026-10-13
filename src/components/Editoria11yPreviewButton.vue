<template>
	<k-view-button :badge="current.badge" :title="current.title" />
</template>

<script>
import check from "../check.js";

export default {
	props: {
		badge: Object,
		title: String,
		check: Object,
	},
	data() {
		return {
			result: null,
		};
	},
	computed: {
		current() {
			return this.result ?? { badge: this.badge, title: this.title };
		},
	},
	watch: {
		check: {
			async handler(target) {
				this.result = null;

				if (!target) {
					return;
				}

				await check(target, this.$panel.api);

				if (this.check === target) {
					this.result = await this.$panel.api.get("editoria11y/button", { page: target.page });
				}
			},
			immediate: true,
		},
	},
};
</script>
