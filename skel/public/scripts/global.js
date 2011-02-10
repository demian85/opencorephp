/**
 * Extensions and utility functions
 */

/**
 * Alias of console.debug()
 */
function fb(s) {
	if (console) console.debug(s);
}

if (!String.prototype.escapeHTML) {
	String.prototype.escapeHTML = function() {
		return this.replace("<", "&lt;").replace(">", "&gt;").replace('"', "&quot;");
	};
}