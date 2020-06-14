<template>
  <div>
    <van-field
      :label="label"
      readonly
      :class="required?'van-cell--required':''"
      :placeholder="placeholder"
      :value="info.text"
      @click="onArea"
    />
    <div>
      <van-popup v-model="isShow" position="bottom">
        <van-area
          :area-list="areaList"
          :value="info.code"
          title="选择地区"
          :columns-num="areaType"
          @confirm="onConfirm"
          @cancel="isShow = false"
        />
      </van-popup>
    </div>
  </div>
</template>

<script>
const infoDefault = {
  text: "",
  code: "",
  id: []
};
import { Area } from "vant";
import { isEmpty } from "@/utils/util";
export default {
  data() {
    return {
      isShow: false,
      areaList: {}
    };
  },
  props: {
    label: {
      type: String
    },
    required: {
      type: Boolean,
      default: false
    },
    placeholder: {
      type: String,
      default: "请选择地区"
    },
    /**
     * 地区范围
     * 1 ==> 省
     * 2 ==> 省市
     * 3 ==> 省市区
     */
    areaType: {
      type: [String, Number],
      default: 3
    },
    info: {
      type: Object,
      default: infoDefault
    }
  },
  watch: {
    // 监听省市区级别
    areaType(n) {
      this.areaList = {};
    }
  },
  methods: {
    onArea() {
      const $this = this;
      if ($this.areaType == -1) {
        $this.$emit("disabled");
        return false;
      }
      $this.$store.dispatch("getArea", true).then(list => {
        $this.areaList = list;
        $this.isShow = true;
      });
    },
    onConfirm(data) {
      const $this = this;
      const areaType = $this.areaType;
      const areaList = $this.$store.getters.areaList;
      let obj = {};
      obj.id = [];
      if (areaType == 1) {
        obj.id[0] = areaList.province_id_list[data[0].code];
      }
      if (areaType == 2) {
        obj.id[0] = areaList.province_id_list[data[0].code];
        obj.id[1] = areaList.city_id_list[data[1].code];
      }
      if (areaType == 3) {
        obj.id[0] = areaList.province_id_list[data[0].code];
        obj.id[1] = areaList.city_id_list[data[1].code];
        obj.id[2] = areaList.county_id_list[data[2].code];
      }
      obj.text = data.map(({ name }) => name).join(" / ");
      obj.code = data[areaType - 1].code;
      $this.$emit("confirm", obj);
      $this.isShow = false;
    }
  },
  components: {
    [Area.name]: Area
  }
};
</script>
<style scoped>
</style>
