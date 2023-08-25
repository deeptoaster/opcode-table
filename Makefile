opcode-table:
	cd src && php -f index.php > ../index.html
	postcss src/opcode-table.css --use autoprefixer > opcode-table.css
	tsc --strict src/opcode-table.ts --outFile opcode-table.js

clean:
	rm -rf index.html opcode-table.css opcode-table.js
