export default function check(target, api) {
	return new Promise((resolve) => {
		const frame = document.createElement("iframe");

		const finish = async (status = "checked") => {
			window.removeEventListener("message", onMessage);
			clearTimeout(timeout);
			frame.remove();

			if (status === "skipped") {
				await api.post("editoria11y/results", {
					page: target.page,
					language: target.language,
					skipped: true,
				});
			}

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

				if (config === null || JSON.parse(config.textContent).sync?.key !== target.id) {
					finish("skipped");
				}
			} catch {
				finish("skipped");
			}
		});
		frame.setAttribute("aria-hidden", "true");
		frame.setAttribute("tabindex", "-1");
		frame.className = "k-editoria11y-scan-frame";
		frame.src = `${target.url}${target.url.includes("?") ? "&" : "?"}editoria11y=scan&editoria11y-key=${encodeURIComponent(target.id)}`;
		document.body.append(frame);
	});
}
