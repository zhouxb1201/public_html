<template>
  <PopupAdvertise
    v-model="show"
    :img-src="params.advimg | BASESRC"
    :link="params.advlink"
    v-if="params.advshow == '1'"
    @close="onCLose"
  />
</template>
<script>
import PopupAdvertise from "@/components/PopupAdvertise";
import { setCookie, getCookie, removeCookie } from "@/utils/storage";
const defaultData = {
  advshow: "0",
  advimg: "/public/platform/images/custom/default/adv-1.jpg",
  advlink: "",
  advrule: "0"
};
export default {
  data() {
    return {
      show: false
    };
  },
  mounted() {
    if (!getCookie("popupAdvRule") && this.params.advshow == "1") {
      this.show = true;
    } else if (!this.day && this.params.advshow == "1") {
      this.show = true;
      removeCookie("popupAdvRule");
    } else {
      if (parseInt(getCookie("popupAdvRule")) !== this.day) {
        setCookie("popupAdvRule", this.day, this.day);
      }
    }
  },
  computed: {
    params() {
      return this.$store.getters.config.wap_pop || defaultData;
    },
    day() {
      const adv = this.$store.getters.config.wap_pop || defaultData;
      const rule = parseInt(adv.advrule);
      let day = 0;
      if (rule) {
        if (rule == 1) {
          day = 1;
        } else if (rule == 2) {
          day = 3;
        } else if (rule == 3) {
          day = 5;
        } else if (rule == 4) {
          day = 30;
        }
      }
      return day;
    }
  },
  methods: {
    onCLose() {
      if (this.day) {
        setCookie("popupAdvRule", this.day, this.day);
      } else {
        removeCookie("popupAdvRule");
      }
    }
  },
  components: {
    PopupAdvertise
  }
};
</script>
