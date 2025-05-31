i18n = (function () {
	return {
		_: function (string, language) {
			if (language == 'en') {
				return string;
			}
			if (language == 'el') {
				switch (string) {
					case "Table %s\n":
					    return "Τραπέζι %s\n"
					    break;
					case "Thank you!":
					    return "Ευχαριστούμε πολύ!"
					    break;
					case "Total %s€\n":
						return "Σύνολο %s€\n"
						break;
				 	default:
				    	return '';
				}
			}
		}
	}
})();