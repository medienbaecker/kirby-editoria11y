<template>
	<span class="k-editoria11y-odometer" role="img" :aria-label="String(value)">
		<span
			v-for="column in columns"
			:key="column.place"
			:class="['k-editoria11y-digit', { 'is-entering': column.entering, 'is-leaving': column.leaving }]"
			aria-hidden="true"
		>
			<span class="k-editoria11y-strip" :style="stripStyle(column)" @transitionend.self="settle(column)">
				<span v-for="n in 30" :key="n">{{ (n - 1) % 10 }}</span>
			</span>
		</span>
	</span>
</template>

<script>
export default {
	props: {
		value: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			columns: [],
		};
	},
	watch: {
		value: {
			handler(value, previous) {
				const digits = String(Math.max(0, value)).split("").reverse();
				const animate = previous !== undefined && !window.matchMedia("(prefers-reduced-motion: reduce)").matches;
				const up = value > previous;

				const leaving = animate
					? this.columns
							.filter((column) => column.place >= digits.length)
							.map((column) => ({ ...column, position: 10, animate: true, leaving: true }))
					: [];

				this.columns = digits
					.map((digit, place) => {
						const column = this.columns.find((column) => column.place === place);
						const target = 10 + Number(digit);

						if (!column || !animate) {
							return { place, position: target, animate: false, entering: !column && animate };
						}

						const current = 10 + (column.position % 10);

						if (current === target) {
							return column;
						}

						const position = up ? (target > current ? target : target + 10) : target < current ? target : target - 10;

						return { place, position, animate: true };
					})
					.concat(leaving)
					.sort((a, b) => b.place - a.place);
			},
			immediate: true,
		},
	},
	methods: {
		stripStyle(column) {
			return {
				transform: `translateY(${(-column.position * 100) / 30}%)`,
				transition: column.animate
					? `transform 320ms cubic-bezier(0.34, 1.35, 0.64, 1) ${column.place * 45}ms`
					: "none",
			};
		},
		settle(column) {
			const index = this.columns.findIndex((entry) => entry.place === column.place);

			if (index === -1) {
				return;
			}

			if (this.columns[index].leaving) {
				this.columns.splice(index, 1);
				return;
			}

			this.columns[index].position = 10 + (this.columns[index].position % 10);
			this.columns[index].animate = false;
		},
	},
};
</script>

<style>
.k-editoria11y-odometer {
	display: inline-flex;
	font-variant-numeric: tabular-nums;
}
.k-editoria11y-odometer .k-editoria11y-digit {
	display: inline-block;
	height: 1lh;
	overflow: hidden;
	mask-image: linear-gradient(to bottom, transparent, #000 25%, #000 75%, transparent);
	transition: opacity 200ms ease-out;
}
.k-editoria11y-odometer .k-editoria11y-digit.is-entering {
	@starting-style {
		opacity: 0;
	}
}
.k-editoria11y-odometer .k-editoria11y-digit.is-leaving {
	opacity: 0;
}
.k-editoria11y-odometer .k-editoria11y-strip {
	display: flex;
	flex-direction: column;
}
.k-editoria11y-odometer .k-editoria11y-strip > span {
	height: 1lh;
}
</style>
