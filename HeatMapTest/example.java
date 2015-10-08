import java.util.HashMap;
import java.util.Map;
import java.util.Iterator;
import java.util.Set;
import java.util.Random;



/*import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;
import java.lang.reflect.Type;*/


public class example {

   public static void main(String args[]) {

      HashMap<String, Integer> hmap = new HashMap<String, Integer>();

      Random rand = new Random();


      for(int i = 1420088400; i <= 1451538000; i+=86400){ // loop through days of year

        int randomNum = rand.nextInt((1000-0)+1); // random number of files sent by device (?)
        String timeStamp = Integer.toString(i); // convert timestamp to string

        hmap.put(timeStamp, randomNum); // insert in Hashmap

      }


      /*

      JSONObject json = new JSONObject();
      json.putALL(hmap);
      System.out.printf("JSON: %s", json.toString(2)); */

       /* Gson gson = new Gson ();
        String json = gson.toJson(hmap);
        System.out.println("json = " + json);*/


      
        // print out hashmap
      Set set = hmap.entrySet();
      Iterator iterator = set.iterator();
      System.out.print("{");
      while(iterator.hasNext()) {
         Map.Entry mentry = (Map.Entry)iterator.next();
         System.out.print("\""+ mentry.getKey() + "\": ");
         System.out.println(mentry.getValue() + ",");
      }
      System.out.print("}");

     

   }
}
