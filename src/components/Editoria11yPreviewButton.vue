<template>
	<k-view-button v-bind="$attrs" :badge="current.badge" :title="current.title" />
</template>

<script>
import check from "../check.js";

export default {
	inheritAttrs: false,
	props: {
		badge: Object,
		title: String,
		check: Object,
	},
	data() {
		return {
			result: null,
			mountedAt: performance.now(),
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

				if (this.check !== target) {
					return;
				}

				const result = await this.$panel.api.get("editoria11y/button", { page: target.page });

				// After a publish the header buttons shift; change the badge once they have settled
				await new Promise((resolve) => setTimeout(resolve, 700 - (performance.now() - this.mountedAt)));

				if (this.check !== target) {
					return;
				}

				if (!this.badge || result.badge) {
					this.result = result;
					return;
				}

				this.result = { ...result, badge: { text: "0", theme: "positive" } };
				await new Promise((resolve) => setTimeout(resolve, 1200));
				this.result = result;
			},
			immediate: true,
		},
	},
};
</script>
