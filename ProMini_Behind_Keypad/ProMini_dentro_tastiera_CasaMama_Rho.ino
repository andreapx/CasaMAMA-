/**
Questo programma permette di far inserire da un utente un codice con il KeyPad. Qualora il codice inserito sia corretto, viene accesso un LED.
Per inviare il codice, una volta digitato interamente è necessario premere *. Mentre per spegnere il LED, oppure re-digitare il codice è necessario premere #
Autore Giacomo Bellazzi
Versione 1.0
Forked by Andrea
Riceve in ingresso tramite seriale vari comandi che devono essere in questo formato:
 1 2 3 4 5 6 7 8 9 10
 | | | | | | | | | |--> frequenza beep x1
 | | | | | | | | |----> frequenza beep x10
 | | | | | | | |------> frequenza beep x100
 | | | | | | |--------> frequenza beep x1000
 | | | | | |----------> frequenza beep x10000
 | | | | |------------> millisecondi beep x1
 | | | |--------------> millisecondi beep x10
 | | |----------------> millisecondi beep x100
 | |------------------> millisecondi beep x1000
 |--------------------> comando

Possibili comandi:
- 0 -> programma i valori di timeout inserimento tra un numero e l'altro
- 1 -> programma durata e frequenza buzzer errore timeout
- 2 -> programma durata e frequenza buzzer pressione tasti numerici
- 3 -> programma durata e frequenza buzzer pressione tasti # e *
- 4 -> stampa le variabili in EEPROM
- 9 -> beep (per n millisecondi e alla frequenza specificati in questo comando)
*/
#include <Keypad.h>
#include<EEPROM.h>


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

#define LED 13
const byte ROWS = 4; //quattro righe
const byte COLS = 3; //tre colonne
char keyInsert[6];
byte i = 0;
byte j = 0;
const byte codeLenght = 6;      //Lunghezza codice
char keys[ROWS][COLS] = {
  {'1','2','3'},
  {'4','5','6'},
  {'7','8','9'},
  {'*','0','#'}
};
const byte rowPins[ROWS] = {9,8,7,6}; //i Pin a cui sono connesse le righe del KeyPad       
const byte colPins[COLS] = {12,11,10}; // i Pin a cui sono connesse le colonne del KeyPad
/*  
 * Mappa pin tastiera (da sx visto da dietro) e pin Arduino:  
 *   TASTIERA   ARDUINO
 *       1         7
 *       2         6
 *       3         /
 *       4         10
 *       5         11
 *       6         12
 *       7         8
 *       8         9
 */
const byte buzzerPin = 5;
const byte tamperInternoPin = 2;
const byte tamperEsternoPin = 3; 

const byte MemAddrTimeoutDelay = 0;          //Address of the variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrTimeoutBuzzerTime = 2;      //Address of the time the buzzer buzzes when the timeout is reached between the pressure of 2 numbers               - The variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrTimeoutBuzzerFreq = 4;      //Address of the frequency at which the buzzer buzzes when he timeout is reached between the pressure of 2 numbers  - The variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrNumPushBuzzerTime = 6;      //Address of the time the buzzer buzzes when a number button is pushed                        - The variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrNumPushBuzzerFreq = 8;      //Address of the frequency at which the buzzer buzzes when a number button is pushed          - The variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrHashPushBuzzerTime = 10;    //Address of the time the buzzer buzzes when the "#" or "*" buttons are pushed                - The variable to be store is an int, so it needs 2 bytes, #0 and #1
const byte MemAddrHashPushBuzzerFreq = 12;    //Address of the frequency at which the buzzer buzzes when the "#" or "*" buttons is pushed   - The variable to be store is an int, so it needs 2 bytes, #0 and #1

unsigned int TimeoutDelay       = EEPROMReadInt(MemAddrTimeoutDelay);           //3000
unsigned int TimeoutBuzzerTime  = EEPROMReadInt(MemAddrTimeoutBuzzerTime);      //400
unsigned int TimeoutBuzzerFreq  = EEPROMReadInt(MemAddrTimeoutBuzzerFreq);      //2000
unsigned int NumPushBuzzerTime  = EEPROMReadInt(MemAddrNumPushBuzzerTime);      //50
unsigned int NumPushBuzzerFreq  = EEPROMReadInt(MemAddrNumPushBuzzerFreq);      //5000
unsigned int HashPushBuzzerTime = EEPROMReadInt(MemAddrHashPushBuzzerTime);     //100
unsigned int HashPushBuzzerFreq = EEPROMReadInt(MemAddrHashPushBuzzerFreq);     //3500


unsigned long lastPushMillis = 0;
unsigned long currentMillis = 0;
char inputChars[15] = {};        // a char array to hold incoming data
byte inputCount = 0;             // counter to put the incoming char in the correct position of the array
boolean stringComplete = false;  // whether the string is complete
unsigned long tamperInternoLastSendMillis = 0;
unsigned long tamperEsternoLastSendMillis = 0;
int TamperNotificationDelay = 2000;       // Interval between every Tamper Notification send to the Master
 
Keypad keypad = Keypad( makeKeymap(keys), rowPins, colPins, ROWS, COLS );
 
void setup(){
  Serial.begin(57600);
  pinMode(LED,OUTPUT);
  pinMode(buzzerPin,OUTPUT);
  pinMode(tamperInternoPin, INPUT_PULLUP);
  pinMode(tamperEsternoPin, INPUT_PULLUP);
}

void loop(){
  if (!digitalRead(tamperInternoPin)){
    if ( (tamperInternoLastSendMillis + TamperNotificationDelay) < currentMillis ){
      Serial.println("ERR:Tamper Interno aperto ");
      tamperInternoLastSendMillis = millis();
    }
  }
  if (!digitalRead(tamperEsternoPin)){
    if ( (tamperEsternoLastSendMillis + TamperNotificationDelay) < currentMillis ){
      Serial.println("ERR:Tamper Esterno aperto ");
      tamperEsternoLastSendMillis = millis();
    }
  }
  if (stringComplete){
    stringComplete = false;
    byte command = inputChars[0] - '0';
    int firstValue = (inputChars[4] - '0') * 1 + (inputChars[3] - '0') * 10 + (inputChars[2] - '0') * 100 + (inputChars[1] - '0') * 1000;  
    int secondValue = (inputChars[9] - '0') * 1 + (inputChars[8] - '0') * 10 + (inputChars[7] - '0') * 100 + (inputChars[6] - '0') * 1000 + (inputChars[5] - '0') * 10000;
    switch (command){
      case 0: // Tempo massimo tra la pressione di un numero e l'altro, dopo di che quanto inserito viene azzerato
              TimeoutDelay = firstValue;
              Serial.print("Writing to EEPROM and RAM TimeoutDelay=");
              Serial.println(TimeoutDelay);
              EEPROMWriteInt(MemAddrTimeoutDelay,firstValue);
              break;
      case 1: TimeoutBuzzerTime = firstValue;
              TimeoutBuzzerFreq = secondValue;
              Serial.print("Writing to EEPROM and RAM TimeoutBuzzerTime=");
              Serial.print(TimeoutBuzzerTime);
              Serial.print(", and TimeoutBuzzerFreq=");
              Serial.println(TimeoutBuzzerFreq);
              EEPROMWriteInt(MemAddrTimeoutBuzzerTime,firstValue);
              EEPROMWriteInt(MemAddrTimeoutBuzzerFreq,secondValue);
              break;
      case 2: NumPushBuzzerTime = firstValue;
              NumPushBuzzerFreq = secondValue;
              Serial.print("Writing to EEPROM and RAM NumPushBuzzerTime=");
              Serial.print(NumPushBuzzerTime);
              Serial.print(", and NumPushBuzzerFreq=");
              Serial.println(NumPushBuzzerFreq);
              EEPROMWriteInt(MemAddrNumPushBuzzerTime,firstValue);
              EEPROMWriteInt(MemAddrNumPushBuzzerFreq, secondValue);
              break;
      case 3: HashPushBuzzerTime = firstValue;
              HashPushBuzzerFreq = secondValue;
              Serial.print("Writing to EEPROM and RAM HashPushBuzzerTime=");
              Serial.print(HashPushBuzzerTime);
              Serial.print(", and HashPushBuzzerFreq=");
              Serial.println(HashPushBuzzerFreq);
              EEPROMWriteInt(MemAddrHashPushBuzzerTime, firstValue);
              EEPROMWriteInt(MemAddrHashPushBuzzerFreq, secondValue);
              break;
      case 4: Serial.print("VarDump TimeoutDelay = ");
              Serial.println(TimeoutDelay);
              Serial.print("VarDump TimeoutBuzzerTime = ");
              Serial.println(TimeoutBuzzerTime);
              Serial.print("VarDump TimeoutBuzzerFreq = ");
              Serial.println(TimeoutBuzzerFreq);
              Serial.print("VarDump NumPushBuzzerTime = ");
              Serial.println(NumPushBuzzerTime);
              Serial.print("VarDump NumPushBuzzerFreq = ");
              Serial.println(NumPushBuzzerFreq);
              Serial.print("VarDump HashPushBuzzerTime = ");
              Serial.println(HashPushBuzzerTime);
              Serial.print("VarDump HashPushBuzzerFreq = ");
              Serial.println(HashPushBuzzerFreq);
              break;
      case 9: Serial.print("Beeping for: ");
              Serial.print(firstValue);
              Serial.print(" at ");
              Serial.print(secondValue);
              Serial.println("hz");
              tone(buzzerPin,secondValue);
              delay(firstValue);
              noTone(buzzerPin);
              break;
    }   
  }

  currentMillis = millis();
  if ( j>0 && (currentMillis - lastPushMillis >= TimeoutDelay) ) {    //Se sono passati più di "TimeoutDelay" millisecondi e l'inserimento non è il primo, azzera quanto inserito e ricomincia con questo inserimento come primo
    tone(buzzerPin,TimeoutBuzzerFreq);
    delay(TimeoutBuzzerTime);
    noTone(buzzerPin);
    Serial.println("Err:Timeout, reinsert first number");
    j= 0;
  }
    
  char key = keypad.getKey();
  if (i==0){
    //Serial.println("Insert PIN to verify...");
    i++;
  }
  if (key != NO_KEY && j<codeLenght){
    currentMillis = millis();
    /*
    if ( j>0 && (currentMillis - lastPushMillis >= TimeoutDelay) ) {    //Se sono passati più di "TimeoutDelay" millisecondi e l'inserimento non è il primo, azzera quanto inserito e ricomincia con questo inserimento come primo
      Serial.print("Timeout, reinsert first number");
      j= 0;
    }
    */
    //Serial.print("#");
    //Serial.print(key);
    keyInsert[j]=key;
    j++;
    lastPushMillis = currentMillis;
    tone(buzzerPin,NumPushBuzzerFreq);
    delay(NumPushBuzzerTime);
    noTone(buzzerPin);
  }
   if(key == '#') {
      //Serial.println("Sending the code to the master...");
      Serial.print("Code:");
      for (byte count = 0; count < codeLenght; count++){
        Serial.print(keyInsert[count]);
      }
      Serial.print("\n");
      tone(buzzerPin,3500);
      delay(100);
      noTone(buzzerPin);
      i=0;
      j=0;
    }  
    if(key == '*'){
      tone(buzzerPin,HashPushBuzzerFreq);
      delay(HashPushBuzzerTime);
      noTone(buzzerPin);
      i=0;
      j=0; 
    }    
}

void serialEvent() {
  while (Serial.available()) {
    // get the new byte:
    char inChar = (char)Serial.read();
    // add it to the inputString:
    inputChars[inputCount] = inChar;
    inputCount++;
    //inputString += inChar;
    // if the incoming character is a newline, set a flag
    // so the main loop can do something about it:
    if (inChar == '\n') {
      stringComplete = true;
      inputCount = 0;
    }
  }
}

String getValue(String data, char separator, int index)
{
    int found = 0;
    int strIndex[] = { 0, -1 };
    int maxIndex = data.length() - 1;

    for (int i = 0; i <= maxIndex && found <= index; i++) {
        if (data.charAt(i) == separator || i == maxIndex) {
            found++;
            strIndex[0] = strIndex[1] + 1;
            strIndex[1] = (i == maxIndex) ? i+1 : i;
        }
    }
    return found > index ? data.substring(strIndex[0], strIndex[1]) : "";
}


