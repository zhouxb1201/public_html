<template>
  <div>
    <Card v-if="isShow == 1" :items="list"/>
    <PanelGroup v-else :items="list"/>
  </div>
</template>

<script>
import Card from "./Card";
import PanelGroup from "./PanelGroup";
export default {
  data() {
    return {

    };
  },
  props: {
    list: [Object, Array],
    isShow:{
      type:String
    }
  },
  components: {
    Card,
    PanelGroup
  }
};
</script>

<style scoped>

</style>