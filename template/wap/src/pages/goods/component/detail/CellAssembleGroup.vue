<template>
  <div>
    <van-cell is-link @click="show=true">
      <div slot="icon" class="title" :style="{color:titleColor}">拼团</div>
      <div class="value">{{info.group_record_count}}人在拼单，可直接参与</div>
      <div slot="right-icon" class="right-box">
        <span class="fs-12">查看更多</span>
        <van-icon name="arrow" class="van-cell__right-icon" />
      </div>
    </van-cell>
    <AssembleItem
      v-for="(item,l) in filterList"
      :key="l"
      :item="item"
      @show-detail="showDetail"
      @callback="callback"
    />
    <div>
      <PopupBottom v-model="show" title="正在拼单">
        <van-cell-group class="list">
          <AssembleItem
            v-for="(item,t) in list"
            :key="t"
            :item="item"
            @show-detail="showDetail"
            @callback="callback"
          />
        </van-cell-group>
      </PopupBottom>
      <PopupAssembleItem
        v-if="activeItem"
        :item="activeItem"
        v-model="showItem"
        @confirm="confirm"
        @callback="callback"
      />
    </div>
  </div>
</template>

<script>
import PopupBottom from "@/components/PopupBottom";
import AssembleItem from "./AssembleItem";
import PopupAssembleItem from "./PopupAssembleItem";
export default {
  data() {
    return {
      show: false,
      showItem: false,
      activeItem: ""
    };
  },
  props: {
    titleColor: {
      type: String,
      default: "#606266"
    },
    info: Object
  },
  computed: {
    list() {
      return this.info.group_record_list || [];
    },
    filterList() {
      return this.info.group_record_list.length
        ? this.info.group_record_list.filter((e, i) => i < 2)
        : [];
    }
  },
  methods: {
    showDetail(id) {
      const item = this.list.filter(({ record_id }) => record_id == id);
      this.activeItem = item[0];
      this.showItem = true;
    },
    confirm(id) {
      this.$emit("confirm", id);
      this.showItem = false;
      this.show = false;
    },
    callback() {
      this.$emit("callback");
    }
  },
  components: {
    PopupBottom,
    AssembleItem,
    PopupAssembleItem
  }
};
</script>
<style scoped>
.title {
  width: 50px;
  color: #606266;
}

.value {
  display: flex;
  flex-flow: column;
  color: #606266;
  font-size: 12px;
}

.right-box {
  display: flex;
  align-items: center;
  color: #909399;
}
</style>
