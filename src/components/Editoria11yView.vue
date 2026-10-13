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
							:text="scanLabel"
							:disabled="rows.length === 0"
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
			<k-stats :reports="reports" size="medium" />
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
					:total="rows.length"
					@paginate="pagination.page = $event.page"
				/>
			</footer>
		</k-section>
	</k-panel-inside>
</template>

<script>
export default {
	props: {
		rows: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			scanning: false,
			stopped: false,
			done: 0,
			total: 0,
			sortBy: "errors",
			sortDirection: "desc",
			pagination: {
				page: 1,
				limit: 50,
			},
		};
	},
	computed: {
		checkedRows() {
			return this.rows.filter((row) => row.checked !== null);
		},
		reports() {
			const errors = this.sum("errors");
			const warnings = this.sum("warnings");
			const unchecked = this.rows.length - this.checkedRows.length;

			return [
				{
					label: this.$t("medienbaecker.editoria11y.stats.pages"),
					value: String(this.checkedRows.filter((row) => row.errors + row.warnings > 0).length),
					icon: "page",
				},
				{
					label: this.$t("medienbaecker.editoria11y.stats.checked"),
					value: `${this.checkedRows.length}/${this.rows.length}`,
					icon: unchecked > 0 ? "clock" : "check",
				},
				{
					label: this.$t("medienbaecker.editoria11y.stats.errors"),
					value: String(errors),
					icon: errors > 0 ? "alert" : "check",
				},
				{
					label: this.$t("medienbaecker.editoria11y.stats.warnings"),
					value: String(warnings),
					icon: warnings > 0 ? "info" : "check",
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

			return [...this.rows].sort((a, b) => {
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
		scanLabel() {
			if (this.scanning) {
				return `${this.$t("medienbaecker.editoria11y.stop")} · ${this.done}/${this.total}`;
			}

			return this.$t("medienbaecker.editoria11y.scan");
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
			const queue = [...this.rows];

			this.scanning = true;
			this.stopped = false;
			this.done = 0;
			this.total = queue.length;

			const refresh = setInterval(() => this.$panel.view.refresh(), 2000);

			const worker = async () => {
				while (queue.length > 0 && this.stopped === false) {
					const row = queue.shift();

					if ((await this.check(row)) === "skipped") {
						await this.$api.post("editoria11y/results", {
							page: row.page,
							language: row.language,
							skipped: true,
						});
					}

					this.done++;
				}
			};

			await Promise.all(Array.from({ length: 4 }, worker));
			clearInterval(refresh);
			await this.$panel.reload();

			if (this.stopped === false) {
				this.$panel.notification.success(
					this.$t("medienbaecker.editoria11y.scanned", { count: this.done }),
				);
			}

			this.scanning = false;
		},
		stop() {
			this.stopped = true;
		},
		check(row) {
			return new Promise((resolve) => {
				const frame = document.createElement("iframe");

				const finish = (status = "checked") => {
					window.removeEventListener("message", onMessage);
					clearTimeout(timeout);
					frame.remove();
					resolve(status);
				};

				const onMessage = (event) => {
					if (event.source === frame.contentWindow && event.data === "editoria11y:checked") {
						finish();
					}
				};

				const timeout = setTimeout(finish, 8000);

				window.addEventListener("message", onMessage);
				frame.addEventListener("load", () => {
					try {
						const config = frame.contentDocument.getElementById("kirby-editoria11y");

						if (config === null || JSON.parse(config.textContent).sync?.key !== row.id) {
							finish("skipped");
						}
					} catch {
						finish("skipped");
					}
				});
				frame.setAttribute("aria-hidden", "true");
				frame.setAttribute("tabindex", "-1");
				frame.className = "k-editoria11y-scan-frame";
				frame.src = `${row.url}${row.url.includes("?") ? "&" : "?"}editoria11y=scan&editoria11y-key=${encodeURIComponent(row.id)}`;
				document.body.append(frame);
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
.k-editoria11y-scan-frame {
	position: fixed;
	top: 0;
	left: -10000px;
	width: 1280px;
	height: 800px;
	border: 0;
}
</style>
