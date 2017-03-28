/*
  Software serial multple serial test

 Receives from the hardware serial, sends to software serial.
 Receives from software serial, sends to hardware serial.

 The circuit:
 * RX is digital pin 10 (connect to TX of other device)
 * TX is digital pin 11 (connect to RX of other device)

 Note:
 Not all pins on the Mega and Mega 2560 support change interrupts,
 so only the following can be used for RX:
 10, 11, 12, 13, 50, 51, 52, 53, 62, 63, 64, 65, 66, 67, 68, 69

 Not all pins on the Leonardo and Micro support change interrupts,
 so only the following can be used for RX:
 8, 9, 10, 11, 14 (MISO), 15 (SCK), 16 (MOSI).

 created back in the mists of time
 modified 25 May 2012
 by Tom Igoe
 based on Mikal Hart's example

 This example code is in the public domain.

Possibili comandi tramite porta seriale:
- 0 ->
- 1 -> Modifica un codice in EEPROM. Formato codice in in ingresso: 1yxxxxxx \n (new line, invio) dove y è il numero di codice da modificare ed xxxxxx è il nuovo codice
- 2 -> Invia un comando seriale ad Arduino installato appena dietro alla tastiera. Formato codice: 2xxxxxxxxxx dove xxxxxxxxxx è il comando da inviare all'altro Arduino (10 caratteri)
- 3 -> Modifica il valore di DoorOpenTimeout in EEPRMOM. Formato codice: 3xxxxx dove xxxxx indica i millisecondi in cui la serratura rimarrà aperta. Massimo 65534
- 4 ->// Non più attiva, limite impostato a 20  --  modifica il numero di codici inseriti (storedCodes). Formato codice: 4000xx dove xx indica il numero di codici, massimo 10
- 5 -> Abilita relay 1 (PIN 12, serratura) per tot secondi. Formato 5xxxxx  dove xxxxx indica i millisecondi in cui il relay rimarrà attivo. Massimo 65534
- 9 -> Scrive su seriale l'elenco dei codici inseriti

 */
#include <SoftwareSerial.h>
#include<EEPROM.h>


int freeRam () {
  extern int __heap_start, *__brkval; 
  int v; 
  return (int) &v - (__brkval == 0 ? (int) &__heap_start : (int) __brkval); 
}
    
//This function will write a 2 byte integer to the eeprom at the specified address and address + 1
void EEPROMWriteInt(int p_address, int p_value){
  byte lowByte = ((p_value >> 0) & 0xFF);
  byte highByte = ((p_value >> 8) & 0xFF);
  
  EEPROM.write(p_address, lowByte);
  EEPROM.write(p_address + 1, highByte);
}

//This function will read a 2 byte integer from the eeprom at the specified address and address + 1
unsigned int EEPROMReadInt(int p_address){
  byte lowByte = EEPROM.read(p_address);
  byte highByte = EEPROM.read(p_address + 1);
  
  return ((lowByte << 0) & 0xFF) + ((highByte << 8) & 0xFF00);
}

SoftwareSerial mySerial(3, 2); // RX, TX

#define LED 13
#define DoorLockPin 12

//Define the addresses used by the EEPROM
//The first 60 are used by the codes, I will leave some space in case in the future I need to store more codes
//Let's start from the byte 200 (leaving space for a total of 33 codes
const byte MemAddrDoorOpenTimeout = 200;
const byte MemAddrStoredCodes = 202;

int DoorOpenTimeout = EEPROMReadInt(MemAddrDoorOpenTimeout);      

char inputChars[30] = {};        // a char array to hold incoming data from Serial
char myinputChars[30] = {};        // a char array to hold incoming data from mySerial
byte inputCount = 0;             // counter to put the incoming char from Serial in the correct position of the array
byte myinputCount = 0;             // counter to put the incoming char from mySerial in the correct position of the array
boolean stringComplete = false;  // whether the string from Serial is complete
boolean mystringComplete = false;  // whether the string from mySerial is complete
boolean codeCommand = false;
boolean errorCommand = false;
char codeChars[5] = {"Code:"};

const byte codeLenght = 6;      //Lunghezza codice
const byte storedCodes = 20;    //Numero di codici memorizzati
char code[10][codeLenght] = {};
// Queste variabili servono come verifica del corretto inserimento del codice
int i = 0;
int j = 0;
int s = 0;
int x = 0;
int zero = 0;         //Variabile per vedere se il codice inserito è 000000 e dare errore di codice non abilitato
char keyInsert[6];
byte codeCorrect = 0;             //Diventa 1 se il codice inserito è corretto


void setup() {
  // Open serial communications and wait for port to open:
  Serial.begin(57600);
  while (!Serial) {
    ; // wait for serial port to connect. Needed for native USB port only
  }
  // set the data rate for the SoftwareSerial port
  mySerial.begin(57600);
  readCodesFromEEPROM(false);
  Serial.println("Setup finished!");
}

void loop() { // run over and over 
  if (stringComplete){
    stringComplete = false;
    byte command = inputChars[0] - '0';
    byte subCommand = (inputChars[2] - '0') * 1 + (inputChars[1] - '0') * 10;
    int firstValue = (inputChars[5] - '0') * 1 + (inputChars[4] - '0') * 10 + (inputChars[3] - '0') * 100 + (inputChars[2] - '0') * 1000 + (inputChars[1] - '0') * 10000;  
    byte count = 1;
    switch (command){
      case 1: Serial.println("Command: 1 - Replace a stored code");       
              Serial.print("subCommand: ");
              Serial.println(subCommand);
              writeCodesToEEPROM(subCommand);                                    //write in EEPROM the new code
              readCodesFromEEPROM(false);                                          //Update the var "code" with the new stored values
              break;
      case 2: Serial.println("Command: 2 - Send the command to the Arduino in the Keyboard");                               //
              while (count < 11){
                mySerial.write(inputChars[count]);
                count++;
              }
              mySerial.write("\n");
              break;
      case 3: Serial.print("Command: 3 - Change in the EEPROM the value of DoorOpenTimeout");
              EEPROMWriteInt(MemAddrDoorOpenTimeout,firstValue);
              DoorOpenTimeout = firstValue;
              break;
      //case 4: Serial.println("Command: 4 - Change the number of stored codes usable");
      //        EEPROMWriteInt(MemAddrStoredCodes,firstValue);
      case 9: Serial.println("Command: 9");
              readCodesFromEEPROM(true);
              break;
    }
    /*                            // DEBUG
    Serial.print("New code: ");
    for (byte stringPos = 0; stringPos < codeLenght; stringPos++){
      //Serial.print(newCode[stringPos]);
      Serial.print(inputChars[stringPos + 2]);
    }
    */
  }
  myserialEvent();
  if (mystringComplete){
    mystringComplete = false;
    byte count = 0;
    count = 0;
    byte verifyCommand = 0;
    codeCommand = true;
    while ( (count < 5) && (myinputChars[count] != '\n') ){
      if (myinputChars[count] == codeChars[count]){
        verifyCommand++;
        //codeCommand = false;
      }
      count++;
    }
    //Serial.println(codeCommand);
    //Serial.print("verifyCommand=");
    //Serial.println(verifyCommand);
    if (verifyCommand == 5){
    //if (codeCommand){
      Serial.print("Received code: ");
      byte count2 = 5;
      byte count3 = 0;
      while (count2 < 11){
        keyInsert[count3] = myinputChars[count2];
        Serial.print(keyInsert[count3]);
        count2++;
        count3++;
      }
      Serial.println();
      byte codeNumber = 0;
      while ( (codeNumber < storedCodes) && x <= codeLenght ){
        zero = 0;
        for(s=0; s<6;s++){
          if (keyInsert[s]==code[codeNumber][s]){
            x++;
            if (keyInsert[s]== '0'){
              zero++;
            }
          }
        }
        if ( x == 6 ){
          if (zero >= 6){
            codeCorrect = 2;
          }else{
            codeCorrect = 1;
          }
        }
        codeNumber++;
        x = 0;
      }
      if(codeCorrect == 1 ){
        Serial.println("The code is correct");
        mySerial.println("9050006000");
        digitalWrite(LED,HIGH);
        digitalWrite(DoorLockPin, HIGH);
        delay(DoorOpenTimeout);
        digitalWrite(DoorLockPin, LOW);
        digitalWrite(LED,LOW);
        for(s=0; s<6;s++){
          keyInsert[s]="";
        }
        codeCorrect = 0;
        x=0;
        i=0;
        j=0;
      }else{
         if (codeCorrect == 2){
           Serial.println("Error:99:Code 000000 not enabled");
           mySerial.println("9100008000");
           zero = 0;
           codeCorrect = 0;
           x=0;
           i=0;
           j=0;
         }else{
           Serial.println("Error:01:The code is incorrect, please retry");
           delay(200);
           mySerial.println("9100008000");
           delay(100);
           codeCorrect = 0;
           x=0;
           i=0;
           j=0;
         }
      }
    }
  }
}

void serialEvent() {
  while (Serial.available()) {
    // get the new byte:
    char inChar = (char)Serial.read();
    // add it to the inputString:
    inputChars[inputCount] = inChar;
    inputCount++;
    // if the incoming character is a newline, set a flag
    // so the main loop can do something about it:
    if (inChar == '\n') {
      stringComplete = true;
      inputCount = 0;
    }
  }
}

void myserialEvent() {
  while (mySerial.available()) {
    // get the new byte:
    char inChar = (char)mySerial.read();
    //Serial.print(inChar);
    // add it to the inputString:
    myinputChars[myinputCount] = inChar;
    myinputCount++;
    // if the incoming character is a newline, set a flag
    // so the main loop can do something about it:
    if (inChar == '\n') {
      mystringComplete = true;
      myinputCount = 0;
    }
  }
}

void writeCodesToEEPROM(byte stringNumber){
  for (byte stringPos = 0; stringPos < codeLenght; stringPos++){
    byte eepromPos = stringPos + (stringNumber * codeLenght);
    EEPROM.write(eepromPos, inputChars[stringPos + 3]);
  }
}

void readCodesFromEEPROM(boolean printCodes){
  char tempString[codeLenght]= {};
  for (byte stringNumber = 0; stringNumber < storedCodes; stringNumber++){
    if (printCodes){
      Serial.print("Code");
      Serial.print(stringNumber);
      Serial.print(":");
    }
    for (byte stringPos = 0; stringPos < codeLenght; stringPos++){
      byte eepromPos = stringPos + (stringNumber * codeLenght);
      tempString[stringPos] = EEPROM.read(eepromPos);
      if (printCodes){
        Serial.print(tempString[stringPos]);
      }
      code[stringNumber][stringPos] = tempString[stringPos];
    }
    if(printCodes){
      Serial.println();
    }
  }
}
