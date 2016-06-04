# ISAC
Project for ISAC

Aplikace zabývající se rozdělěním novinových článků do kategorií a vyhledávání v nich.
Našli jsme dataset obsahující novinové články roydělené do 5 kategorií, business, entertainment, politics, sport, tech. V každé kategorii je přibližně 400-500 článků. Články jsme si připravili pomocí programu VecText a příkazu:

perl vectext-cmdline.pl --input=output_b.txt --class_position=1 --output_dir=. --output_file=output_b --local_weights="Term Frequency (TF)" --output_format=arff --output_decimal_places=0 --stopwords_file=stopwords.txt --min_global_frequency=2 --encoding="utf8" --case="lower_case" --sort_attributes="alphabetically"

, který jsme použili pro každou kategorii. Jak je vidět použili jsme omezení na stopwords. - můžeme vypsat pár slov z toho souboru
Získana data v souborech s příponou .arff jsme nechali zpracovat náším vlastním programem v javě, který vygeneruje výstup ve formě matice, kde je to slovo X dokument. Matice ukazuje počet výskytu jednotlivých slov v jednotlivých dokumentech(článcích) a pro každé slovo obsahuje celkový počet výskutu a frekvenci výskytu v rámci kategorie(docFreq).

Samotná webová aplikace funguje jako vyhledávač nad těmito maticemi. Prvotní rozdělení dle kategorií zůstalo a lze vyhledávat až v každé kategorii. Vyhledávání původně bylo jen jako součet vyhledaných slov v každém dokumentu a zobrazeno dle nejvyšších hodnot. Toto nebylo příliš optimální. Druhé vyhledávání bylo vylepšené, tzv. Shared word count, kde se pro každé slovo počítala hodnota dle vzorce 1+1/docFreq. Pokud slovo v dokumentu bylo obsažené tak hodnota byla přičtena do součtu pro dokument, zase byl výpis seřazen dle nejvyšších hodnot. Poslední a výsledné vyhledávání je ohodnocené (shared word count) a prohledává jak jednotlivá slova v dané kategorii tak i různé varianty slovních spojení pro příslušné matice vytvořené s parametrem n-grams=2, maximální n-grams máme vytvořené pro 5. Varianty slov jsou spojení slov jak jdou po sobě.
Příklad: vyhledávání "high speed downlink packet access"
  - kombinace vytvoří např: high speed, high downlink, speed downlink, downlink packet acess, ...

Pro tento příklad jsem provedl vyhledávání také všech možných variant těchto 5 slov - výsledky pro jednotlivé dokumenty jsou v souboru pokusy.txt - z toho kdyžtak udělat taky grafy.

Taky jsme udělali podkategorie vycházející z více omezených slov (více stopwords) seřazených dle celkového počtu výkytu. Kliknutí na podkategorii ukáže výpis dokumentů v kterých se nacházejí slovo podkategorie.
