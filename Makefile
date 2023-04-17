opcode-table:
	cd src && php -f index.php > ../index.html
	postcss src/opcode-table.css --use autoprefixer > opcode-table.css

clean:
	rm -rf index.html opcode-table.css
