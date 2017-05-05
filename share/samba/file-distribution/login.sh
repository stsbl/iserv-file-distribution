#!/bin/bash

String="Dieser Arbeitsplatz befindet sich im Dateiverteilungs und -abgabe-Modus!\n"
String+="\n"
String+="Die Dateien/Aufgabenstellung, die Ihnen zugewiesen wurden, finden Sie im Ordner File Distribution-Assignment, "
String+="die Ergebnisse werden im Ordner File Distribution-Return gespeichert.\n"
String+="\n"
String+="Viel Erfolg!"

zenity --info --text="$String" --title="IServ" &
