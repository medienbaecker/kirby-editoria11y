<template>
	<k-panel-inside class="k-editoria11y-view">
		<k-header>
			{{ $t("medienbaecker.editoria11y.title") }}
			<template #buttons>
				<k-button-group>
					<div class="k-view-button">
						<k-button
							:icon="scanning ? 'cancel' : 'refresh'"
							:theme="scanning ? 'negative' : null"
							:text="scanning ? $t('medienbaecker.editoria11y.stop') : $t('medienbaecker.editoria11y.scan')"
							:disabled="live.length === 0"
							variant="filled"
							size="sm"
							responsive="text"
							@click="scanning ? stop() : scan()"
						/>
					</div>
				</k-button-group>
			</template>
		</k-header>

		<k-section>
			<dl class="k-stats" data-size="medium">
				<div
					v-for="report in reports"
					:key="report.key"
					class="k-stat"
					:data-theme="report.theme"
				>
					<dt class="k-stat-label">
						<k-icon :type="report.icon" />
						{{ report.label }}
					</dt>
					<dd class="k-stat-value">
						<editoria11y-odometer :value="report.value" /><template v-if="report.total !== undefined">/{{ report.total }}</template>
					</dd>
				</div>
			</dl>
		</k-section>

		<k-section :headline="$t('medienbaecker.editoria11y.pages')">
			<k-table
				:columns="columns"
				:rows="paginatedRows"
				:index="false"
				:empty="$t('medienbaecker.editoria11y.empty')"
				@header="onHeader"
			>
				<template #header="{ column, columnIndex, label }">
					<button v-if="column.sortable" type="button" class="k-editoria11y-sort">
						{{ label }}
						<k-icon
							v-if="columnIndex === sortBy"
							:type="sortDirection === 'asc' ? 'angle-up' : 'angle-down'"
						/>
						<span v-if="columnIndex === sortBy" class="sr-only">
							{{ $t(`medienbaecker.editoria11y.sort.${sortDirection}`) }}
						</span>
					</button>
					<template v-else>{{ label }}</template>
				</template>
			</k-table>

			<footer class="k-bar k-collection-footer">
				<k-pagination
					v-bind="pagination"
					:details="true"
					:total="live.length"
					@paginate="pagination.page = $event.page"
				/>
			</footer>
		</k-section>

	</k-panel-inside>
</template>

<script>
import check from "../check.js";
import Editoria11yOdometer from "./Editoria11yOdometer.vue";

export default {
	components: {
		"editoria11y-odometer": Editoria11yOdometer,
	},
	props: {
		rows: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			live: this.rows.map((row) => ({ ...row })),
			frozen: null,
			scanning: false,
			stopped: false,
			done: 0,
			sortBy: "errors",
			sortDirection: "desc",
			pagination: {
				page: 1,
				limit: 50,
			},
		};
	},
	beforeDestroy() {
		this.leave();
	},
	beforeUnmount() {
		this.leave();
	},
	watch: {
		rows(rows) {
			if (!this.scanning) {
				this.live = rows.map((row) => ({ ...row }));
			}
		},
	},
	computed: {
		checkedRows() {
			return this.live.filter((row) => row.checked !== null);
		},
		reports() {
			const total = this.live.length;
			const done = this.checkedRows.filter((row) => row.errors + row.warnings === 0).length;

			return [
				{
					key: "done",
					label: this.$t("medienbaecker.editoria11y.stats.done"),
					value: done,
					total,
					icon: done === total ? "check" : "accessibility",
					theme: done === total ? "positive" : null,
				},
				{
					key: "checked",
					label: this.$t("medienbaecker.editoria11y.stats.checked"),
					value: this.checkedRows.length,
					total,
					icon: this.checkedRows.length < total ? "clock" : "checklist",
				},
				{
					key: "errors",
					label: this.$t("medienbaecker.editoria11y.stats.errors"),
					value: this.sum("errors"),
					icon: "alert",
				},
				{
					key: "warnings",
					label: this.$t("medienbaecker.editoria11y.stats.warnings"),
					value: this.sum("warnings"),
					icon: "info",
				},
			];
		},
		columns() {
			return {
				title: {
					label: this.$t("medienbaecker.editoria11y.column.page"),
					type: "url",
					mobile: true,
					sortable: true,
				},
				checked: {
					label: this.$t("medienbaecker.editoria11y.column.checked"),
					width: "11rem",
					sortable: true,
				},
				errors: {
					label: this.$t("medienbaecker.editoria11y.column.errors"),
					width: "7rem",
					align: "right",
					mobile: true,
					sortable: true,
				},
				warnings: {
					label: this.$t("medienbaecker.editoria11y.column.warnings"),
					width: "9rem",
					align: "right",
					mobile: true,
					sortable: true,
				},
			};
		},
		sortedRows() {
			const direction = this.sortDirection === "asc" ? 1 : -1;

			if (this.frozen) {
				return [...this.live].sort((a, b) => this.frozen.get(a.id) - this.frozen.get(b.id));
			}

			return [...this.live].sort((a, b) => {
				if ((a.checked === null) !== (b.checked === null)) {
					const uncheckedFirst = this.sortBy === "checked" && this.sortDirection === "asc";

					return (a.checked === null) === uncheckedFirst ? -1 : 1;
				}

				if (this.sortBy === "errors") {
					return direction * (a.errors - b.errors) || b.warnings - a.warnings;
				}

				if (this.sortBy === "warnings") {
					return direction * (a.warnings - b.warnings) || b.errors - a.errors;
				}

				const left = a[this.sortBy] ?? "";
				const right = b[this.sortBy] ?? "";

				if (typeof left === "number") {
					return direction * (left - right);
				}

				return direction * String(left).localeCompare(String(right));
			});
		},
		paginatedRows() {
			const start = (this.pagination.page - 1) * this.pagination.limit;

			return this.sortedRows.slice(start, start + this.pagination.limit).map((row) => ({
				id: row.id,
				title: { text: row.title, href: row.link },
				errors: row.checked === null ? "–" : String(row.errors),
				warnings: row.checked === null ? "–" : String(row.warnings),
				checked: row.checked === null ? "" : this.relative(row.checked),
			}));
		},
	},
	methods: {
		relative(date) {
			const format = new Intl.RelativeTimeFormat(this.$panel.translation.code.replace("_", "-"), {
				numeric: "auto",
			});
			const day = (value) => new Date(value).setHours(0, 0, 0, 0);
			const days = Math.round((day(date) - day(Date.now())) / 86400000);

			if (days > -7) {
				return format.format(days, "day");
			}

			if (days > -30) {
				return format.format(Math.round(days / 7), "week");
			}

			if (days > -365) {
				return format.format(Math.round(days / 30), "month");
			}

			return format.format(Math.round(days / 365), "year");
		},
		sum(key) {
			return this.checkedRows.reduce((sum, row) => sum + row[key], 0);
		},
		onHeader({ column, columnIndex }) {
			if (column.sortable !== true) {
				return;
			}

			if (this.sortBy === columnIndex) {
				this.sortDirection = this.sortDirection === "asc" ? "desc" : "asc";
			} else {
				this.sortBy = columnIndex;
				this.sortDirection = ["errors", "warnings", "checked"].includes(columnIndex) ? "desc" : "asc";
			}

			this.pagination.page = 1;
		},
		async scan() {
			const queue = [...this.sortedRows];
			const before = new Map(queue.map((row) => [row.id, (row.errors ?? 0) + (row.warnings ?? 0)]));
			const reduced = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
			let stage = Promise.resolve();

			const play = (apply, slow) => {
				stage = stage.then(() => {
					apply();
					return new Promise((resolve) => setTimeout(resolve, reduced ? 0 : slow ? 220 : 25));
				});

				return stage;
			};

			const worker = async () => {
				while (queue.length > 0 && this.stopped === false) {
					const row = queue.shift();

					await check(row, this.$panel.api);

					const result = await this.$panel.api.get("editoria11y/result", {
						page: row.page,
						language: row.language,
					});
					const clean = result.checked !== null && result.errors + result.warnings === 0;

					await play(() => {
						if (result.skipped) {
							this.live = this.live.filter((live) => live.id !== row.id);
						} else {
							Object.assign(row, {
								errors: result.errors,
								warnings: result.warnings,
								checked: result.checked,
							});

							if (clean) {
								this.flash(row);
							}
						}

						this.done++;
					}, result.errors + result.warnings < before.get(row.id));
				}
			};

			queue.forEach((row) => (row.checked = null));

			this.frozen = new Map(queue.map((row, index) => [row.id, index]));
			this.scanning = true;
			this.stopped = false;
			this.done = 0;

			try {
				await Promise.all(Array.from({ length: 4 }, worker));
			} finally {
				this.frozen = null;
				this.scanning = false;
			}

			if (this.destroyed) {
				return;
			}

			if (this.stopped === false) {
				this.$panel.notification.success(this.$t("medienbaecker.editoria11y.scanned", { count: this.done }));
			}

			await this.$panel.view.refresh();
		},
		stop() {
			this.stopped = true;
		},
		leave() {
			this.destroyed = true;
			this.stopped = true;
		},
		flash(row) {
			this.$nextTick(() => {
				const tr = this.$el.querySelector(`.k-table tbody a[href$="${CSS.escape(row.link)}"]`)?.closest("tr");

				if (tr) {
					tr.classList.remove("k-editoria11y-better");
					void tr.offsetWidth;
					tr.classList.add("k-editoria11y-better");
				}
			});
		},
	},
};
</script>

<style>
.k-editoria11y-view th.k-table-column:has(.k-editoria11y-sort) {
	padding: 0;
}
.k-editoria11y-sort {
	display: flex;
	white-space: nowrap;
	align-items: center;
	justify-content: space-between;
	gap: var(--spacing-1);
}
.k-editoria11y-view [data-align="right"] .k-editoria11y-sort {
	justify-content: flex-end;
}
.k-editoria11y-better > td {
	animation: k-editoria11y-better 600ms ease-out;
}
@keyframes k-editoria11y-better {
	from {
		background: light-dark(var(--color-green-200), var(--color-green-800));
	}
}
.k-editoria11y-view .k-stat[data-theme="positive"] .k-icon {
	color: light-dark(var(--theme-color-700), var(--theme-color-600));
}
.k-editoria11y-scan-frame {
	position: fixed;
	top: 0;
	left: -10000px;
	width: 1280px;
	height: 800px;
	border: 0;
}
</style>
