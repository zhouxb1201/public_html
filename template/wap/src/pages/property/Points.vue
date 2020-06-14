<template>
  <div class="property-points bg-f8">
    <Navbar :title="navbarTitle" />
    <div class="point-box van-hairline--bottom">
      <div>{{$store.state.member.memberSetText.point_style}}</div>
      <div class="point-text">{{point}}</div>
    </div>
    <van-cell :title="$store.state.member.memberSetText.point_style+'明细'" />
    <List
      class="list"
      v-model="loading"
      :finished="finished"
      :error.sync="error"
      :is-empty="isListEmpty"
      @load="loadList"
    >
      <van-cell
        :class="item.sign === 1 ? 'positive' : 'negative'"
        :title="item.number"
        :label="item.type_name"
        :value="item.create_time"
        v-for="(item,index) in list"
        :key="index"
      />
    </List>
  </div>
</template>

<script>
import sfc from "@/utils/create";
import { GET_ASSETPOINTS } from "@/api/property";
import { list } from "@/mixins";
export default sfc({
  name: "property-points",
  data() {
    return {
      point: 0
    };
  },
  mixins: [list],
  computed: {
    navbarTitle() {
      const { point_style } = this.$store.state.member.memberSetText;
      let title = point_style;
      document.title = title;
      return title;
    }
  },
  mounted() {
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    this.loadList();
  },
  methods: {
    loadList(init) {
      const $this = this;
      GET_ASSETPOINTS($this.params)
        .then(({ data }) => {
          $this.point = data.point;
          let list = data.point_detail.data;
          $this.pushToList(list, data.point_detail.page_count, init);
        })
        .catch(() => {
          $this.loadError();
        });
    }
  }
});
</script>

<style scoped>
.property-points >>> .point-box {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-flow: column;
  height: 100px;
  background: #fff;
  margin-bottom: 10px;
}

.property-points >>> .point-text {
  color: #ff454e;
  font-size: 20px;
  font-weight: 800;
  margin-top: 10px;
}
.van-cell.positive >>> .van-cell__title span {
  color: #4b0;
}
.van-cell.negative >>> .van-cell__title span {
  color: #ff454e;
}
</style>
