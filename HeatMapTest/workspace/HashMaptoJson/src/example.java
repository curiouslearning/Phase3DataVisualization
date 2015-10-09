import java.util.HashMap;
import java.util.Map;
import java.util.Iterator;
import java.util.Set;
import java.util.Random;



import com.google.gson.Gson;



public class example {

   public static void main(String args[]) {

      HashMap<String, Integer> hmap = new HashMap<String, Integer>();

      Random rand = new Random();


      for(int i = 1420088400 /*1*/; i <= 1451538000 /*365*/; i+=86400 /*1*/){ // loop through days of year

        int numFiles = rand.nextInt((1000-0)+1); // random number of files sent by device (?)
        String timeStamp = Integer.toString(i); // convert timestamp to string

        hmap.put(timeStamp, numFiles); // insert in Hashmap

      }


      // convert to json
       Gson gson = new Gson ();
       String json = gson.toJson(hmap);
       System.out.println(json);  

   }
}
